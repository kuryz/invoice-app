<?php
namespace App\Models;
use App\Core\Model;
class InvoiceItem extends Model
{
    protected static string $table = 'invoice_items';
    protected static string $primaryKey = 'id';
    protected array $fillable = ['invoice_id','description','quantity','unit_price','line_total'];
    protected array $casts = ['id'=>'integer','invoice_id'=>'integer','quantity'=>'float','unit_price'=>'float','line_total'=>'float','created_at'=>'datetime'];
}
