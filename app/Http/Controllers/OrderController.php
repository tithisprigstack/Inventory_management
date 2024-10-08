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
                    $newPurchaseOrderItem->ordered_quantity = $inventoryDetail['reminderQuantity'];
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
        $poId = $request->input('poId');
        $orderItemDetails = $request->input('orderItemDetails');

        $purchaseOrder = PurchaseOrder::where('id', $poId)->whereIn('status', [1, 2])->first();

        if ($purchaseOrder) {
            $poReceivedFlag = true;
            $poPartialReceivedFlag = false;

            foreach ($orderItemDetails as $orderItemDetail) {

                $purchaseOrderItem = PurchaseOrderItem::where('id', $orderItemDetail['itemId'])->first();

                if ($purchaseOrderItem) {
                    $currentReceivedQuantity = $purchaseOrderItem->current_received_quantity;
                    $orderedQuantity = $purchaseOrderItem->ordered_quantity;
                    $newReceivedQuantity = $orderItemDetail['itemQuantity'];

                    if (($newReceivedQuantity + $currentReceivedQuantity) <= $orderedQuantity) {
                        $purchaseOrderItem->current_received_quantity += $newReceivedQuantity;
                        $purchaseOrderItem->save();

                        $Inventory = Inventory::where('id', $purchaseOrderItem->inventory_id)->first();
                        $Inventory->quantity += $newReceivedQuantity;
                        $Inventory->save();

                        if ($purchaseOrderItem->current_received_quantity < $purchaseOrderItem->ordered_quantity) {
                            $poPartialReceivedFlag = true;
                            $poReceivedFlag = false;
                        }
                    } else {
                        $poPartialReceivedFlag = true;
                        $poReceivedFlag = false;
                        Log::error("given order item has exceed the purchase order item order quantity");
                        continue;
                    }
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'given order item not found',
                    ], 400);
                }
            }
            if ($poPartialReceivedFlag) {
                $purchaseOrder->status = 2;
            } elseif ($poReceivedFlag) {
                $purchaseOrder->status = 3;
            }

            $purchaseOrder->delivery_date = date("Y-m-d");
            $purchaseOrder->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Purchase order quantities successfully updated in inventory.'
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'purchase order for this id has not found or already received',
            ], 400);
        }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
