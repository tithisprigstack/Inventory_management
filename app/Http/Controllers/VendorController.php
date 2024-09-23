<?php

namespace App\Http\Controllers;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function getVendors()
    {
        $vendors = Vendor::all();
        return $vendors;
    }

    public function getVendorDetails($vid)
    {
        $vendorDetails = vendor::with('products')->where('id', $vid)->first();
        return $vendorDetails;
    }
}
