<?php

namespace App\Http\Controllers;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function getVendors($skey, $sortkey, $sflag, $page, $limit)
    {
        $allVendors = Vendor::with('inventoryDetails.inventory')->where('status', 1);

        if ($skey != 'null') {
            $allVendors->where('name', 'like', "%$skey%")
                ->orWhere('email', 'like', "%$skey%")
                ->orWhere('company_name', 'like', "%$skey%")
                ->orWhereHas('inventoryDetails', function ($query) use ($skey) {
                    $query->whereHas('inventory', function ($subquery) use ($skey) {
                        $subquery->where('name', 'like', "%$skey%");
                    });
                });
        }

        if ($sortkey != 'null') {
            $allVendors->orderBy($sortkey, $sflag);
        } else {
            $allVendors->orderBy('id', 'desc');
        }
        return $allVendors->paginate($limit, ['*'], 'page', $page);
    }

    public function getVendorDetails($vid)
    {
        return vendor::with('inventoryDetails.inventory')->where('id', $vid)->first();
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
                $checkEmailExists = Vendor::where('email', $email)->first();
                if ($checkEmailExists) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'This email is already exists with some vendor!',
                    ], 400);
                }
                $newVendor = new Vendor();
                $newVendor->name = $name;
                $newVendor->contact_num = $contactNum;
                $newVendor->email = $email;
                $newVendor->address = $address;
                $newVendor->company_name = $companyName;
                $newVendor->status = 1;
                $newVendor->save();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Vendor details added successfully',
                ], 200);
            } else {
                $vendorExists = Vendor::where('id', $vendorId)->first();
                if ($vendorExists) {
                    $checkEmailExists = Vendor::where('email', $email)->where('id','!=',$vendorExists->id)->first();
                if ($checkEmailExists) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'This email is already exists with other vendor',
                    ], 400);
                }
                    $vendorExists->update([
                        'name' => $name,
                        'contact_num' => $contactNum,
                        'email' => $email,
                        'address' => $address,
                        'company_name' => $companyName,
                    ]);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Vendor details updated successfully',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Vendor with this id do not exist',
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
            Vendor::where('id', $vid)->update(['status' => 2]);
            return 'success';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function getVendorsData()
    {
        return Vendor::with('inventoryDetails.inventory')->where('status', 1)->get();
    }
}
