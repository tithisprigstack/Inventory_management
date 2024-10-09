<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReceiveLog extends Model
{
    use HasFactory;

 protected $fillable = [
    'purchase_order_id',
    'purchase_order_item_id',
    'remaining_ordered_quantity',
    'received_quantity',
    'extra_quantity'
 ];

    public function purchaseOrder()
    {
       return  $this->belongsTo(PurchaseOrder::class,'purchase_order_id','id');
    }
    public function purchaseOrderItem()
    {
       return  $this->belongsTo(PurchaseOrderItem::class,'purchase_order_item_id','id');
    }
}
