<?php
namespace App\Models;
use App\Core\Model;
class InvoiceApiKey extends Model
{
    protected static string $table = 'invoice_api_keys';
    protected static string $primaryKey = 'id';
    protected array $fillable = ['workspace_id','label','mode','key_prefix','key_hash','last_used_at','revoked_at'];
    protected array $hidden = ['key_hash'];
    protected array $casts = ['id'=>'integer','workspace_id'=>'integer','created_at'=>'datetime','last_used_at'=>'datetime','revoked_at'=>'datetime'];
}
