<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends  Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'name',
        'contact_num',
        'email',
        'address',
        'company_name'
    ];

    public function inventories()
    {
       return $this->hasMany(Inventory::class,'vendor_id','id');
    }
    public function purchaseOrders()
    {
       return $this->hasMany(PurchaseOrder::class,'vendor_id','id');
    }
}
