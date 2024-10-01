<?php

namespace App\Http\Controllers;
use App\Models\Inventory;
use App\Models\InventoryUsage;
use Illuminate\Http\Request;

class UtilizationController extends Controller
{

    public function addInventoryUtilization(Request $request)
    {
        try{
            $inventoryId = $request->input('inventoryId');
            $quantity = $request->input('quantity');
            $usagePurpose = $request->input('usagePurpose');
            $usedDate = $request->input('usedDate');

            $checkInventory = Inventory::where('id',$inventoryId)->first();

            if($checkInventory->quantity < $quantity)
            {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This item does not have enough stock',
                ], 400);
            }
            else{
                $checkInventory->quantity -= $quantity;
                $checkInventory->save();

                $newInventoryUsge = new InventoryUsage();
                $newInventoryUsge->inventory_id = $checkInventory->id;
                $newInventoryUsge->usage_purpose = $usagePurpose;
                $newInventoryUsge->quantity = $quantity;
                $newInventoryUsge->used_date = $usedDate;
                $newInventoryUsge->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Utilization data added successfully',
                ], 200);
        }
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
