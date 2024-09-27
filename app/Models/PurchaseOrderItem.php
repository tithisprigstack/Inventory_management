<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderItem extends Model
{
    use HasFactory;

 protected $fillable = [
    'purchase_order_id',
    'inventory_id',
    'quantity',
    'price'
 ];

    public function inventory()
    {
       return  $this->belongsTo(Inventory::class,'item_id','id');
    }
    public function purchaseOrder()
    {
       return  $this->belongsTo(PurchaseOrder::class,'purchase_order_id','id');
    }
}
