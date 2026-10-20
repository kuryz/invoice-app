<?php
namespace App\Models;
use App\Core\Model;
class Invoice extends Model
{
    protected static string $table = 'invoices';
    protected static string $primaryKey = 'id';
    protected array $fillable = ['workspace_id','public_id','invoice_number','external_id','customer_name','customer_email','customer_phone','currency','subtotal','tax_rate','tax_amount','discount_amount','total_amount','amount_due','platform_fee','fee_payer','customer_fee','merchant_fee','status','due_date','description','hosted_url','success_url','cancel_url','metadata_json','paid_at'];
    protected array $casts = ['id'=>'integer','workspace_id'=>'integer','subtotal'=>'float','tax_rate'=>'float','tax_amount'=>'float','discount_amount'=>'float','total_amount'=>'float','amount_due'=>'float','platform_fee'=>'float','customer_fee'=>'float','merchant_fee'=>'float','created_at'=>'datetime','updated_at'=>'datetime','paid_at'=>'datetime'];
}
