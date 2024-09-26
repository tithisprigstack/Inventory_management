<?php

namespace App\Http\Controllers;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function getVendors()
    {
        return Vendor::with('inventories')->get();
    }

    public function getVendorDetails($vid)
    {
        return vendor::with('inventories')->where('id', $vid)->first();
    }

    public function addUpdateVendor(Request $request)
    {
        try {
            $addUpdateFlag = $request->input('addUpdateFlag');
            $name = $request->input('name');
            $contactNum = $request->input('contactNum');
            $email = $request->input('email');
            $address = $request->input('address');
            $companyName = $request->input('companyName');
            $vendorId = $request->input('vendorId');

            if ($addUpdateFlag == 0) {
                $checkEmailExists = Vendor::where( 'email', $email)->first();
                if ($checkEmailExists) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Email is unique for all vendor',
                    ], 400);
                }
                $newVendor = new Vendor();
                $newVendor->name = $name;
                $newVendor->contact_num = $contactNum;
                $newVendor->email = $email;
                $newVendor->address = $address;
                $newVendor->company_name = $companyName;
                $newVendor->save();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Vendor details added successfully',
                ], 200);
            } else {
                $vendorExists = Vendor::where('id', $vendorId)->first();
                if ($vendorExists) {
                    $vendorExists->update([
                        'name' => $name,
                        'contact_num' => $contactNum,
                        'email' => $email,
                        'address' => $address,
                        'company_name' => $companyName,
                    ]);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'vendor details updated successfully',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'vendor not Found',
                    ], 400);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function deleteVendor($vid)
    {
        try {
            Vendor::where('id', $vid)->delete();
            return 'success';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
