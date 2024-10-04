<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory;

 protected $fillable = [
    'vendor_id',
    'total_amount',
    'status',
    'po_pdf',
    'delivery_date'
 ];

    public function purchaseInventories()
    {
       return  $this->hasMany(PurchaseOrderItem::class,'purchase_order_id','id');
    }
    public function vendor()
    {
       return  $this->belongsTo(Vendor::class,'vendor_id','id');
    }
}
