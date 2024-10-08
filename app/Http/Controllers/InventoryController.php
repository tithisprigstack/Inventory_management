<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryDetail;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function getCategories()
    {
        return Category::all();
    }

    public function allInventories($skey, $sortkey, $sflag, $page, $limit)
    {
        $inventoryData = Inventory::with('category', 'inventoryDetail.vendor', 'purchaseLogs.purchaseOrder');
        if ($skey != 'null') {
            $inventoryData->where('name', 'like', "%$skey%")
                ->orWhereHas('category', function ($q) use ($skey) {
                    $q->where('name', 'like', "%$skey%");
                })
                ->orWhereHas('inventoryDetail', function ($query) use ($skey) {
                    $query->whereHas('vendor', function ($subquery) use ($skey) {
                        $subquery->where('name', 'like', "%$skey%");
                    });
                });
        }

        if ($sortkey != 'null') {
            $inventoryData->orderBy($sortkey, $sflag);
        } else {
            $inventoryData->orderBy('id', 'desc');
        }
        $inventoryData = $inventoryData->paginate($limit, ['*'], 'page', $page);
        $inventoryData->getCollection()->transform(function ($inventory) {
            $inventory->needsPurchaseOrderFlag = 0;
            $inventory->hasActivePurchaseOrderFlag = 0;
            if ($inventory->quantity < $inventory->reminder_quantity) {
                $inventory->needsPurchaseOrderFlag = 1;
            }
            if ($inventory['purchaseLogs']) {
                foreach ($inventory['purchaseLogs'] as $purchaseLog) {
                    if ($purchaseLog->purchaseOrder->status == 1 || $purchaseLog->purchaseOrder->status == 2) {
                        $inventory->hasActivePurchaseOrderFlag = 1;
                    }
                }
            }
            return $inventory;
        });
        return $inventoryData;
    }
    public function getInventoryDetails($id)
    {
        return Inventory::with('inventoryDetail.vendor', 'category', 'usageHistory', 'purchaseLogs.purchaseOrder.vendor')->where('id', $id)->first();
    }

    public function addUpdateInventory(Request $request)
    {
        try {
            $addUpdateFlag = $request->input('addUpdateFlag');
            $name = $request->input('name');
            $description = $request->input('description');
            $quantity = $request->input('quantity');
            $price = $request->input('price');
            $inventoryId = $request->input('inventoryId');
            $reminderQuantity = $request->input('reminderQuantity');
            $categoryId = $request->input('categoryId');
            $vendorId = $request->input('vendorId');

            if ($addUpdateFlag == 0) {
                $nameExist = Inventory::where('name', $name)->first();
                if ($nameExist) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Same Item Name Exist',
                    ], 400);
                }
                $newInventory = new Inventory();
                $newInventory->name = $name;
                $newInventory->description = $description;
                $newInventory->quantity = $quantity;
                $newInventory->price = $price;
                $newInventory->reminder_quantity = $reminderQuantity;
                $newInventory->category_id = $categoryId;
                $newInventory->save();

                $newInventoryDetails = new InventoryDetail();
                $newInventoryDetails->inventory_id = $newInventory->id;
                $newInventoryDetails->vendor_id = $vendorId;
                $newInventoryDetails->quantity = $quantity;
                $newInventoryDetails->price = $price;
                $newInventoryDetails->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Item added successfully',
                ], 200);
            } else {
                $inventoryExists = Inventory::where('id', $inventoryId)->first();
                if ($inventoryExists) {
                    $nameExist = Inventory::where('name', $name)->where('id', '!=', $inventoryExists->id)->first();
                    if ($nameExist) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Same Item Name Exist',
                        ], 400);
                    }
                    $inventoryExists->update([
                        'name' => $name,
                        'description' => $description,
                        'quantity' => $quantity,
                        'reminder_quantity' => $reminderQuantity,
                        'price' => $price,
                        'category_id' => $categoryId
                    ]);

                    InventoryDetail::where('inventory_id', $inventoryExists->id)->update(['vendor_id' => $vendorId, 'quantity' => $quantity, 'price' => $price]);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Item updated successfully',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Item not Found',
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

    public function deleteInventory($id)
    {
        try {
            Inventory::where('id', $id)->delete();
            return 'success';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
