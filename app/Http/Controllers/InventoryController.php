<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function getCategories()
    {
        return Category::all();
    }
    public function allInventories($skey, $sortkey, $sflag, $page, $limit)
    {
        $inventoryData = Inventory::join('vendors', 'inventories.vendor_id', '=', 'vendors.id')
            ->select('inventories.*', 'vendors.name as vendor_name');

        if ($skey != 'null') {
            $inventoryData->where(function ($query) use ($skey) {
                $query->where('inventories.name', 'like', "%$skey%")
                    ->orWhere('inventories.sku', 'like', "%$skey%")
                    ->orWhere('vendors.name', 'like', "%$skey%");
            });
        }

        if ($sortkey != 'null') {
            if ($sortkey == 'vendor_name') {
                $inventoryData->orderBy('vendors.name', $sflag);
            } else {
                $inventoryData->orderBy($sortkey, $sflag);
            }
        } else {
            $inventoryData->orderBy('id', 'desc');
        }
        $inventoryData = $inventoryData->paginate($limit, ['*'], 'page', $page);

        $inventoryData->getCollection()->transform(function ($inventory) {
            $inventory->purchaseOrderFlag = ($inventory->quantity <= $inventory->reminder_quantity) ? 1 : 0;
            return $inventory;
        });
        return $inventoryData;
    }

    public function getInventoryDetails($id)
    {
        return Inventory::with('vendor', 'category', 'usageHistory')->where('id', $id)->first();
    }

    public function addUpdateInventory(Request $request)
    {
        try {
            $addUpdateFlag = $request->input('addUpdateFlag');
            $sku = $request->input('sku');
            $name = $request->input('name');
            $description = $request->input('description');
            $quantity = $request->input('quantity');
            $price = $request->input('price');
            $categoryId = $request->input('categoryId');
            $vendorId = $request->input('vendorId');
            $inventoryId = $request->input('inventoryId');
            $reminderQuantity = $request->input('reminderQuantity');

            if ($addUpdateFlag == 0) {
                $checkSkuExists = Inventory::where('sku', $sku)->first();
                if ($checkSkuExists) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'SKU is unique for all item',
                    ], 400);
                }
                $newInventory = new Inventory();
                $newInventory->name = $name;
                $newInventory->description = $description;
                $newInventory->sku = $sku;
                $newInventory->quantity = $quantity;
                $newInventory->price = $price;
                $newInventory->category_id = $categoryId;
                $newInventory->vendor_id = $vendorId;
                $newInventory->reminder_quantity = $reminderQuantity;
                $newInventory->save();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Item added successfully',
                ], 200);
            } else {
                $inventoryExists = Inventory::where('id', $inventoryId)->first();
                if ($inventoryExists) {
                    $checkSkuExists = Inventory::where('sku', $sku)->where('id','!=',$inventoryExists->id)->first();
                if ($checkSkuExists) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'SKU is unique for all item',
                    ], 400);
                }
                    $inventoryExists->update([
                        'name' => $name,
                        'description' => $description,
                        'sku' => $sku,
                        'quantity' => $quantity,
                        'reminder_quantity' => $reminderQuantity,
                        'price' => $price,
                        'category_id' => $categoryId,
                        'vendor_id' => $vendorId
                    ]);

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
