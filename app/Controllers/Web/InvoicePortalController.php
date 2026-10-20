<?php
namespace App\Controllers\Web;
use App\Controllers\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;
class InvoicePortalController extends Controller
{
 public function landing(): string { return $this->render('invoice/landing'); }
 public function pay(Request $request, Response $response, string $public_id): string
 {
  $db=Database::getInstance()->getConnection();$s=$db->prepare('SELECT i.*,w.name AS business_name,w.support_email FROM invoices i JOIN invoice_workspaces w ON w.id=i.workspace_id WHERE i.public_id=? LIMIT 1');$s->execute([$public_id]);$invoice=$s->fetch(PDO::FETCH_ASSOC);if(!$invoice){http_response_code(404);return '<h1>Invoice not found</h1>';}
  $items=$db->prepare('SELECT * FROM invoice_items WHERE invoice_id=?');$items->execute([$invoice['id']]);$invoice['items']=$items->fetchAll(PDO::FETCH_ASSOC);return $this->render('invoice/pay',['invoice'=>$invoice]);
 }
}
