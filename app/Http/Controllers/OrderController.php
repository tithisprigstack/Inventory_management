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
            $inventoryDetails = $request->input('inventoryDetails');
            $vendorId = $request->input('vendorId');

            $checkVendor = Vendor::where('id', $vendorId)->where('status', 1)->first();
            if ($checkVendor) {
                $newPurchaseOrder = new PurchaseOrder();
                $newPurchaseOrder->vendor_id = $vendorId;
                $newPurchaseOrder->status = 1;
                $newPurchaseOrder->save();

                $total_amount = 0;
                $inventoryData = [];
                foreach ($inventoryDetails as $inventoryDetail) {
                    $inventory = Inventory::with('category')->where('id', $inventoryDetail['inventoryId'])->first();
                    $newPurchaseOrderItem = new PurchaseOrderItem();
                    $newPurchaseOrderItem->purchase_order_id = $newPurchaseOrder->id;
                    $newPurchaseOrderItem->inventory_id = $inventoryDetail['inventoryId'];
                    $newPurchaseOrderItem->quantity = $inventoryDetail['reminderQuantity'];
                    $newPurchaseOrderItem->price = $inventory->price;
                    $newPurchaseOrderItem->save();
                    $total_amount += $inventoryDetail['reminderQuantity'] * $inventory->price;
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
                return response()->json([
                    'status' => 'success',
                    'message' => 'Purchased order generated successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This Vendor is currently not active',
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function allPurchaseOrders($skey, $sortKey, $sflag, $page, $limit)
    {
        $purchaseData = PurchaseOrder::with('purchaseInventories.inventory', 'vendor');

        if ($skey != 'null') {
            if ($skey == 'pending') {
                $purchaseData->where('status', 'like', 1);
            } elseif ($skey == 'received') {
                $purchaseData->where('status', 'like', 2);
            } else {
                $purchaseData->where('id', 'like', "%$skey%")
                    ->orWhere('total_amount', 'like', "%$skey%")
                    ->orWhereHas('vendor', function ($q) use ($skey) {
                        $q->where('name', 'like', "%$skey%");
                    });
            }
        }

        if ($sortKey != 'null') {
            $purchaseData->orderBy($sortKey, $sflag);
        } else {
            $purchaseData->orderBy('id', 'desc');
        }

        return $purchaseData->paginate($limit, ['*'], 'page', $page);
    }

    public function poDetails($pid)
    {
        return PurchaseOrder::with('purchaseInventories.inventory', 'vendor')->where('id', $pid)->first();
    }

    public function updateOrderQuantity(Request $request)
    {
        try {
            $poIds = $request->input('poIds');

            foreach ($poIds as $poId) {
                $purchaseOrder = PurchaseOrder::where('id', $poId)->where('status', 1)->first();

                if ($purchaseOrder) {
                    $purchaseOrder->update(['status' => 2, 'delivery_date' => date("Y-m-d")]);
                    $purchaseOrderItems = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)->get();

                    foreach ($purchaseOrderItems as $purchaseOrderItem) {
                        $Inventory = Inventory::where('id', $purchaseOrderItem->inventory_id)->first();
                        $Inventory->quantity += $purchaseOrderItem->quantity;
                        $Inventory->save();
                    }
                } else {
                    Log::error("we have skip po which is once received");
                    continue;
                }
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Purchased order and their ordered quantity added to inventory item',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
