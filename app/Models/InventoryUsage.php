<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryUsage extends Model
{
    use HasFactory,SoftDeletes;

 protected $fillable = [
    'quantity',
    'inventory_id',
    'used_date'
 ];
    public function inventory()
    {
       return  $this->belongsTo(Inventory::class,'inventory_id','id');
    }
}
