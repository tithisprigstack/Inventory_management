<?php

namespace App\Http\Controllers;
use App\Models\Inventory;
use App\Models\InventoryUsage;
use Illuminate\Http\Request;

class UtilizationController extends Controller
{

    public function addInventoryUtilization(Request $request)
    {
        $inventoryId = $request->input('inventoryId');
        $quantity = $request->input('quantity');

        $checkInventory = Inventory::where('id',$inventoryId)->first();

        if($checkInventory->quantity < $quantity)
        {
            return 'not enough stock';
        }
        else{
            $checkInventory->quantity -= $quantity;
            $checkInventory->save();

            $newInventoryUsge = new InventoryUsage();
            $newInventoryUsge->inventory_id = $checkInventory->id;
            $newInventoryUsge->quantity = $quantity;
            $newInventoryUsge->used_date = now();
            $newInventoryUsge->save();

            return 'new inventory usage added';
        }
    }
}
