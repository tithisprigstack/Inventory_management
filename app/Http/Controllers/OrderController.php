<?php

namespace App\Http\Controllers;
use App\Mail\PurchaseOrderMail;
use App\Mail\SendOrderToVendorMail;
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
use Illuminate\Support\Facades\Storage;
use PDF;

class OrderController extends Controller
{

    public function generatePurchaseOrder(Request $request)
    {
        try {
            $vendorInventoryDetails = $request->input('vendorInventoryDetails');
            foreach ($vendorInventoryDetails as $vendorInventoryDetail) {
                $vendorId = $vendorInventoryDetail['vendor_id'];
                if (!isset($groupedInventoryDetails[$vendorId])) {
                    $groupedInventoryDetails[$vendorId] = [];
                }
                $groupedInventoryDetails[$vendorId][] = $vendorInventoryDetail;
            }

            foreach ($groupedInventoryDetails as $vendorId => $inventorydetails) {
                $checkVendor = Vendor::find($vendorId);
                if ($checkVendor) {
                    $newPurchaseOrder = new PurchaseOrder();
                    $newPurchaseOrder->vendor_id = $vendorId;
                    $newPurchaseOrder->status = 1;
                    $newPurchaseOrder->order_date = now();
                    $newPurchaseOrder->save();

                    $total_amount = 0;
                    $inventoryData = [];

                    foreach ($inventorydetails as $inventoryDetail) {
                        $inventory = Inventory::with('category')->where('id', $inventoryDetail['inventory_id'])->first();
                        $newPurchaseOrderItem = new PurchaseOrderItem();
                        $newPurchaseOrderItem->purchase_order_id = $newPurchaseOrder->id;
                        $newPurchaseOrderItem->inventory_id = $inventoryDetail['inventory_id'];
                        $newPurchaseOrderItem->quantity = $inventoryDetail['reminder_quantity'];
                        $newPurchaseOrderItem->price = $inventory->price;
                        $newPurchaseOrderItem->save();
                        $total_amount += $inventoryDetail['reminder_quantity'] * $inventory->price;
                        $inventoryData[] = ['inventory' => $inventory, 'poItemDetails' => $newPurchaseOrderItem];
                    }
                    $newPurchaseOrder->update(['total_amount' => $total_amount]);
                    $data = [
                        'vendorDetails' => $checkVendor,
                        'inventoryDetails' => $inventoryData,
                    ];
                    $filename = 'Orders/' . $newPurchaseOrder->id . '_' . time() . '.pdf';
                    $pdf = PDF::loadView('emails.newPurchaseOrder', compact('data'));
                    Storage::disk('public')->put($filename, $pdf->output());
                    $path = public_path('documents/' . $filename);
                    $newPurchaseOrder->update(['po_pdf' => $filename]);
                    // try {
                    //     Mail::to($checkVendor->email)->send(new SendOrderToVendorMail($path,$data));
                    // } catch (\Exception $e) {
                    //     Log::error("Mail sending failed: " . $e->getMessage());
                    // }
                } else {
                    Log::error("This vendor is currently not active");
                    continue;
                }
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Purchased order generated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function allPurchaseOrders()
    {
        return PurchaseOrder::with('purchaseInventories.inventory','vendor')->get();
    }

    public function poDetails($pid)
    {
       return PurchaseOrder::with('purchaseInventories.inventory','vendor')->where('id', $pid)->first();
    }
}
