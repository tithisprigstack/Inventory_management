<?php

namespace App\Http\Controllers;
use App\Mail\LowStockReminderMail;
use App\Models\Inventory;
use App\Models\InventoryUsage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class UtilizationController extends Controller
{

    public function addInventoryUtilization(Request $request)
    {
        try {
            $inventoryId = $request->input('inventoryId');
            $quantity = $request->input('quantity');
            $usagePurpose = $request->input('usagePurpose');
            $usedDate = $request->input('usedDate');

            $checkInventory = Inventory::where('id', $inventoryId)->first();
            if ($checkInventory->quantity < $quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This item does not have enough stock to fulfil your utilization quantity request',
                ], 400);
            } elseif ($checkInventory->quantity < $checkInventory->reminder_quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This item is below the reminder quantity first order new quantity to utilize item',
                ], 400);
            } else {

                $checkInventory->quantity -= $quantity;
                $checkInventory->save();

                $newInventoryUsge = new InventoryUsage();
                $newInventoryUsge->inventory_id = $checkInventory->id;
                $newInventoryUsge->usage_purpose = $usagePurpose;
                $newInventoryUsge->quantity = $quantity;
                $newInventoryUsge->used_date = $usedDate;
                $newInventoryUsge->save();

                $user = User::find(1);
                if ($checkInventory->quantity < $checkInventory->reminder_quantity) {
                    $data = [
                        'userDetails' => $user,
                        'inventoryDetails' => $checkInventory
                    ];
                    // try {
                    //     Mail::to($user->email)->send(new LowStockReminderMail($data));
                    // } catch (\Exception $e) {
                    //     Log::error("Mail sending failed: " . $e->getMessage());
                    // }
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Utilization data added successfully',
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
