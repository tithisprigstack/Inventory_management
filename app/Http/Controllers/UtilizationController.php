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

class UtilizationController extends Controller
{

    // public function generatePurchaseOrder($productId)
    // {
    //     $checkProduct = Product::with('vendor')->where('id', $productId)->first();

    //     if ($checkProduct) {
    //         $checkVendor = Vendor::where('id', $checkProduct->vendor_id)->first();
    //         if (!$checkVendor) {
    //             return 'This vendor is not available ,You have to assign new vendor to this product.';
    //         }

    //         $newPurchaseOrder = new PurchaseOrder();
    //         $newPurchaseOrder->vendor_id = $checkVendor->id;
    //         $newPurchaseOrder->product_id = $productId;
    //         $newPurchaseOrder->quantity = $checkProduct->reminder_quantity;
    //         $newPurchaseOrder->price = $checkProduct->price;
    //         $newPurchaseOrder->status = 1;
    //         $newPurchaseOrder->order_date = now();
    //         $newPurchaseOrder->save();

    //         try {
    //             Mail::to($checkVendor->email)->send(new PurchaseOrderMail($checkProduct->reminder_quantity, $checkProduct->name));
    //         } catch (\Exception $e) {
    //             Log::error("Mail sending failed: " . $e->getMessage());
    //         }
    //         return 'success';
    //     }
    // }

    public function generatePOForMultipleProduct()
    {
        $allInventories = Inventory::whereColumn('quantity', '<=', 'reminder_quantity')->with('vendor')->get();
        $vendorInventories = $allInventories->groupBy('vendor_id');
        foreach ($vendorInventories as $vendorId => $inventories) {
            $checkVendor = Vendor::find($vendorId);
            if ($checkVendor) {
                $newPurchaseOrder = new PurchaseOrder();
                $newPurchaseOrder->vendor_id = $vendorId;
                $newPurchaseOrder->status = 1;
                $newPurchaseOrder->order_date = now();
                $newPurchaseOrder->save();

                $total_amount = 0;
                foreach ($inventories as $inventory) {
                    $newPurchaseOrderProduct = new PurchaseOrderItem();
                    $newPurchaseOrderProduct->purchase_order_id = $newPurchaseOrder->id;
                    $newPurchaseOrderProduct->inventory_id = $inventory->id;
                    $newPurchaseOrderProduct->quantity = $inventory->reminder_quantity;
                    $newPurchaseOrderProduct->price = $inventory->price;
                    $newPurchaseOrderProduct->save();

                    $total_amount += $inventory->reminder_quantity * $inventory->price;
                }
                $newPurchaseOrder->update(['total_amount' => $total_amount]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Purchased order generated successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vendor with this item is not active you have to update the item',
                ], 400);
            }
        }
    }
}
