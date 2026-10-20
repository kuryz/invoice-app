<?php
namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

/** API-first invoicing. API keys are stored as SHA-256 hashes and scoped to a workspace. */
class InvoiceApiController extends Controller
{
    private PDO $db;
    public function __construct() { parent::__construct(); $this->db = Database::getInstance()->getConnection(); }

    private function workspace(Request $request, Response $response): array
    {
        $raw = $request->getHeader('X-API-Key') ?: $request->getBearerToken();
        if (!$raw || !preg_match('/^nb_(live|test)_[A-Za-z0-9]{24,}$/', $raw)) $response->error('A valid X-API-Key is required.', 401);
        $stmt = $this->db->prepare('SELECT k.id AS api_key_id,k.workspace_id,k.mode,w.name AS workspace_name,w.default_currency FROM invoice_api_keys k JOIN invoice_workspaces w ON w.id=k.workspace_id WHERE k.key_hash=? AND k.revoked_at IS NULL AND w.status="active" LIMIT 1');
        $stmt->execute([hash('sha256', $raw)]); $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) $response->error('Invalid or revoked API key.', 401);
        $this->db->prepare('UPDATE invoice_api_keys SET last_used_at=NOW() WHERE id=?')->execute([$row['api_key_id']]);
        return $row;
    }

    public function create(Request $request, Response $response): void
    {
        $w = $this->workspace($request,$response); $d=$request->getBody();
        $required=['customer_name','customer_email','currency','items'];
        foreach($required as $f) if(!isset($d[$f]) || $d[$f]==='' || ($f==='items' && !is_array($d[$f]))) $response->error("Missing or invalid field: {$f}",422);
        if(!filter_var($d['customer_email'],FILTER_VALIDATE_EMAIL)) $response->error('customer_email must be a valid email.',422);
        $currency=strtoupper((string)$d['currency']); if(!preg_match('/^[A-Z]{3}$/',$currency)) $response->error('currency must be a 3-letter ISO currency code.',422);
        if(count($d['items'])<1 || count($d['items'])>100) $response->error('Provide between 1 and 100 invoice items.',422);
        $feePayer=$d['fee_payer']??'customer'; if(!in_array($feePayer,['customer','merchant','split'],true)) $response->error('fee_payer must be customer, merchant, or split.',422);
        $subtotal=0.0; $items=[];
        foreach($d['items'] as $i=>$item){
            if(!is_array($item) || trim((string)($item['description']??''))==='' || !is_numeric($item['quantity']??null) || !is_numeric($item['unit_price']??null)) $response->error('Each item needs description, quantity and unit_price.',422);
            $qty=(float)$item['quantity']; $price=(float)$item['unit_price'];
            if($qty<=0 || $qty>1000000 || $price<0 || $price>100000000000) $response->error('Item quantity or price is outside allowed limits.',422);
            $line=round($qty*$price,2); $subtotal=round($subtotal+$line,2);
            $items[]=['description'=>mb_substr(trim($item['description']),0,255),'quantity'=>$qty,'unit_price'=>$price,'line_total'=>$line];
        }
        $taxRate=max(0,min(100,(float)($d['tax_rate']??0))); $tax=round($subtotal*$taxRate/100,2); $discount=max(0,min($subtotal,(float)($d['discount']??0))); $amount=round($subtotal+$tax-$discount,2);
        $feeRate=(float)($_ENV['INVOICE_PLATFORM_FEE_PERCENT']??'1.5'); $feeFixed=(float)($_ENV['INVOICE_PLATFORM_FEE_FIXED']??'0');
        $fee=round(($amount*$feeRate/100)+$feeFixed,2); $customerFee=$feePayer==='customer'?$fee:($feePayer==='split'?round($fee/2,2):0); $merchantFee=round($fee-$customerFee,2); $amountDue=round($amount+$customerFee,2);
        $number=trim((string)($d['invoice_number']??'')); if($number==='') $number='INV-'.strtoupper(bin2hex(random_bytes(4)));
        $publicId=bin2hex(random_bytes(16)); $dueDate=$d['due_date']??null; if($dueDate!==null && (!is_string($dueDate)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$dueDate))) $response->error('due_date must use YYYY-MM-DD.',422);
        $this->db->beginTransaction();
        try {
            $stmt=$this->db->prepare('INSERT INTO invoices (workspace_id,public_id,invoice_number,external_id,customer_name,customer_email,customer_phone,currency,subtotal,tax_rate,tax_amount,discount_amount,total_amount,amount_due,platform_fee,fee_payer,customer_fee,merchant_fee,status,due_date,description,success_url,cancel_url,metadata_json,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$w['workspace_id'],$publicId,$number,isset($d['external_id'])?substr((string)$d['external_id'],0,120):null,mb_substr(trim($d['customer_name']),0,160),$d['customer_email'],isset($d['customer_phone'])?substr((string)$d['customer_phone'],0,40):null,$currency,$subtotal,$taxRate,$tax,$discount,$amount,$amountDue,$fee,$feePayer,$customerFee,$merchantFee,'unpaid',$dueDate,isset($d['description'])?mb_substr((string)$d['description'],0,1000):null,$d['success_url']??null,$d['cancel_url']??null,json_encode($d['metadata']??new \stdClass(),JSON_UNESCAPED_UNICODE),date('Y-m-d H:i:s'),date('Y-m-d H:i:s')]);
            $id=(int)$this->db->lastInsertId(); $ins=$this->db->prepare('INSERT INTO invoice_items (invoice_id,description,quantity,unit_price,line_total,created_at) VALUES (?,?,?,?,?,NOW())');
            foreach($items as $item) $ins->execute([$id,$item['description'],$item['quantity'],$item['unit_price'],$item['line_total']]);
            $this->db->prepare('INSERT INTO invoice_events (invoice_id,event_type,payload_json,created_at) VALUES (?,?,?,NOW())')->execute([$id,'invoice.created',json_encode(['status'=>'unpaid','fee_payer'=>$feePayer,'platform_fee'=>$fee])]);
            $this->db->commit();
        } catch(\Throwable $e){$this->db->rollBack();throw $e;}
        $base=rtrim($_ENV['APP_URL']??'', '/'); $this->db->prepare('UPDATE invoices SET hosted_url=? WHERE id=?')->execute([$base.'/pay/'.$publicId,$id]);
        $response->success(['id'=>$id,'public_id'=>$publicId,'invoice_number'=>$number,'status'=>'unpaid','currency'=>$currency,'subtotal'=>$subtotal,'tax_amount'=>$tax,'discount_amount'=>$discount,'total_amount'=>$amount,'amount_due'=>$amountDue,'platform_fee'=>$fee,'fee_payer'=>$feePayer,'customer_fee'=>$customerFee,'merchant_fee'=>$merchantFee,'hosted_url'=>$base.'/pay/'.$publicId,'due_date'=>$dueDate], 'Invoice created.',201);
    }

    public function index(Request $request, Response $response): void
    {
        $w=$this->workspace($request,$response); $limit=max(1,min(100,(int)$request->query('limit',20))); $offset=max(0,(int)$request->query('offset',0));
        $stmt=$this->db->prepare('SELECT public_id,invoice_number,external_id,customer_name,customer_email,currency,total_amount,platform_fee,fee_payer,status,due_date,hosted_url,created_at FROM invoices WHERE workspace_id=? ORDER BY id DESC LIMIT '.$limit.' OFFSET '.$offset); $stmt->execute([$w['workspace_id']]);
        $response->success(['invoices'=>$stmt->fetchAll(PDO::FETCH_ASSOC),'limit'=>$limit,'offset'=>$offset]);
    }
    public function show(Request $request, Response $response, string $public_id): void
    {
        $w=$this->workspace($request,$response); $stmt=$this->db->prepare('SELECT * FROM invoices WHERE workspace_id=? AND public_id=? LIMIT 1'); $stmt->execute([$w['workspace_id'],$public_id]); $invoice=$stmt->fetch(PDO::FETCH_ASSOC); if(!$invoice)$response->error('Invoice not found.',404);
        $items=$this->db->prepare('SELECT description,quantity,unit_price,line_total FROM invoice_items WHERE invoice_id=?');$items->execute([$invoice['id']]); unset($invoice['id'],$invoice['workspace_id']); $invoice['items']=$items->fetchAll(PDO::FETCH_ASSOC); $invoice['metadata']=json_decode($invoice['metadata_json']??'{}',true);unset($invoice['metadata_json']);$response->success($invoice);
    }
    public function cancel(Request $request, Response $response, string $public_id): void
    {
        $w=$this->workspace($request,$response); $s=$this->db->prepare('SELECT id,status FROM invoices WHERE workspace_id=? AND public_id=?');$s->execute([$w['workspace_id'],$public_id]);$i=$s->fetch(PDO::FETCH_ASSOC);if(!$i)$response->error('Invoice not found.',404);if($i['status']!=='unpaid')$response->error('Only unpaid invoices can be cancelled.',409);
        $this->db->prepare('UPDATE invoices SET status="cancelled",updated_at=NOW() WHERE id=?')->execute([$i['id']]);$this->db->prepare('INSERT INTO invoice_events (invoice_id,event_type,payload_json,created_at) VALUES (?,?,?,NOW())')->execute([$i['id'],'invoice.cancelled','{}']);$response->success(['public_id'=>$public_id,'status'=>'cancelled'],'Invoice cancelled.');
    }
}
