<?php

namespace App\Http\Controllers;
use App\Mail\PurchaseOrderMail;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderProduct;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{

    // public function generatePurchaseOrder(Request $request)
    // {
    //     $vendorInventoryDetails = $request->input('vendorInventoryDetails');

    //     foreach($vendorInventoryDetails as $vendorInventoryDetail)
    //     {
    //         $checkVendor = Vendor::find($vendorInventoryDetail['vendor_id']);
    //         if($checkVendor)
    //         {
    //             $newPurchaseOrder = new PurchaseOrder();
    //             $newPurchaseOrder->vendor_id = $vendorInventoryDetail['vendor_id'];
    //             $newPurchaseOrder->status = 1;
    //             $newPurchaseOrder->order_date = now();
    //             $newPurchaseOrder->save();

    //             $total_amount = 0;
    //             foreach($vendorInventoryDetail['inventoryDetails'] as $inventoryDetail)
    //             {
    //                 $newPurchaseOrderItem = new PurchaseOrderItem();
    //                 $newPurchaseOrderItem->purchase_order_id = $newPurchaseOrder->id;
    //                 $newPurchaseOrderItem->inventory_id = $inventoryDetail['inventory_id'];
    //                 $newPurchaseOrderItem->quantity =  $inventoryDetail['reminder_quantity'];
    //                 $newPurchaseOrderItem->price =  $inventoryDetail['price'];
    //                 $newPurchaseOrderItem->save();
    //                 $total_amount += $inventoryDetail['reminder_quantity'] * $inventoryDetail['price'];
    //             }
    //             $newPurchaseOrder->update(['total_amount' => $total_amount]);
    //             return response()->json([
    //                 'status' => 'success',
    //                 'message' => 'Purchased order generated successfully',
    //             ], 200);
    //         }
    //     }
    // }

    // public function generatePOForMultipleProduct()
    // {
    //     $allInventories = Inventory::whereColumn('quantity', '<=', 'reminder_quantity')->with('vendor')->get();
    //     $vendorInventories = $allInventories->groupBy('vendor_id');
    //     foreach ($vendorInventories as $vendorId => $inventories) {
    //         $checkVendor = Vendor::find($vendorId);
    //         if ($checkVendor) {
    //             $newPurchaseOrder = new PurchaseOrder();
    //             $newPurchaseOrder->vendor_id = $vendorId;
    //             $newPurchaseOrder->status = 1;
    //             $newPurchaseOrder->order_date = now();
    //             $newPurchaseOrder->save();

    //             $total_amount = 0;
    //             foreach ($inventories as $inventory) {
    //                 $newPurchaseOrderProduct = new PurchaseOrderItem();
    //                 $newPurchaseOrderProduct->purchase_order_id = $newPurchaseOrder->id;
    //                 $newPurchaseOrderProduct->inventory_id = $inventory->id;
    //                 $newPurchaseOrderProduct->quantity = $inventory->reminder_quantity;
    //                 $newPurchaseOrderProduct->price = $inventory->price;
    //                 $newPurchaseOrderProduct->save();

    //                 $total_amount += $inventory->reminder_quantity * $inventory->price;
    //             }
    //             $newPurchaseOrder->update(['total_amount' => $total_amount]);
    //             return response()->json([
    //                 'status' => 'success',
    //                 'message' => 'Purchased order generated successfully',
    //             ], 200);
    //         } else {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Vendor with this item is not active you have to update the item',
    //             ], 400);
    //         }
    //     }
    // }
}
