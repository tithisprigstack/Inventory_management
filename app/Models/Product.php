<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory,SoftDeletes;

 protected $fillable = [
    'name',
    'description',
    'sku',
    'quantity',
    'price',
    'category_id',
    'vendor_id'
 ];

    public function category()
    {
       return  $this->belongsTo(Category::class,'category_id','id');
    }
    public function vendor()
    {
       return  $this->belongsTo(Vendor::class,'vendor_id','id');
    }
}
