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

    public function products()
    {
       return $this->hasMany(Product::class,'vendor_id','id');
    }
}
