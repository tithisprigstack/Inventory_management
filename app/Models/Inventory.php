<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory,SoftDeletes;

 protected $fillable = [
    'name',
    'description',
    'quantity',
    'reminder_quantity',
    'price',
    'category_id'
 ];

    public function category()
    {
       return  $this->belongsTo(Category::class,'category_id','id');
    }
    public function inventoryDetail()
    {
       return  $this->hasOne(InventoryDetail::class,'inventory_id','id');
    }

    public function usageHistory()
    {
        return  $this->hasMany(InventoryUsage::class,'inventory_id','id');
    }
}
