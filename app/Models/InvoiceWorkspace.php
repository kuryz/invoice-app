<?php
namespace App\Models;
use App\Core\Model;
class InvoiceWorkspace extends Model
{
    protected static string $table = 'invoice_workspaces';
    protected static string $primaryKey = 'id';
    protected array $fillable = ['name','slug','support_email','default_currency','status'];
    protected array $casts = ['id'=>'integer','created_at'=>'datetime','updated_at'=>'datetime'];
}
