<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class InventoryDetail extends Model
{
    use HasFactory;

 protected $fillable = [
    'quantity',
    'inventory_id',
    'vendor_id',
    'price'
 ];

 public function vendor()
 {
    return  $this->belongsTo(Vendor::class,'vendor_id','id');
 }

    public function inventory()
    {
       return  $this->belongsTo(Inventory::class,'inventory_id','id');
    }

}
