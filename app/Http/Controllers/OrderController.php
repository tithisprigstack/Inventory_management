<?php

namespace App\Http\Controllers;
use App\Mail\PurchaseOrderMail;
use App\Mail\SendOrderToVendorMail;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceiveLog;
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
                $user = User::find(1);
                $filename = $user->id .'/'.'PurchaseOrders/' . $newPurchaseOrder->id . '_' . time() . '.pdf';
                $pdf = PDF::loadView('emails.newPurchaseOrder', compact('data'));
                Storage::disk('public')->put($filename, $pdf->output());
                $path = public_path('documents/'. $filename);
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

    public function allPurchaseOrders($statusflag, $skey, $sortKey, $sflag, $page, $limit)
    {
        $purchaseData = PurchaseOrder::with('purchaseInventories.inventory', 'vendor','purchaseReceiveLogs');
        if ($statusflag == 1) {
    $purchaseData->where('purchase_orders.status',1);
        } else {
            $purchaseData->where('purchase_orders.status',2);
        }
        if ($skey != 'null') {
                $purchaseData->where('id', 'like', "%$skey%")
                    ->orWhere('total_amount', 'like', "%$skey%")
                    ->orWhereHas('vendor', function ($q) use ($skey) {
                        $q->where('name', 'like', "%$skey%");
                    });
    }
        if ($sortKey != 'null') {
            if($sortKey == 'vendorName')
            {
                $purchaseData->join('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
                         ->orderBy('vendors.name', $sflag);
            }
            else
            {
                $purchaseData->orderBy($sortKey, $sflag);
            }

        } else {
            $purchaseData->orderBy('id', 'desc');
        }

        return $purchaseData->paginate($limit, ['*'], 'page', $page);
    }


    public function poDetails($pid)
    {
        return PurchaseOrder::with('purchaseInventories.inventory', 'vendor','purchaseReceiveLogs')->where('id', $pid)->first();
    }

    public function updateOrderQuantity(Request $request)
    {
        $poId = $request->input('poId');
        $orderItemDetails = $request->input('orderItemDetails');
        $note = $request->input('note');

        $purchaseOrder = PurchaseOrder::where('id', $poId)->where('status', 1)->first();

        if ($purchaseOrder) {
            $poReceivedFlag = true;

            foreach ($orderItemDetails as $orderItemDetail) {
                $purchaseOrderItem = PurchaseOrderItem::where('id', $orderItemDetail['itemId'])->first();

                if ($purchaseOrderItem) {
                    $orderedQuantity = $purchaseOrderItem->ordered_quantity;
                    $currentReceivedQuantity = $purchaseOrderItem->current_received_quantity;
                    $newReceivedQuantity = $orderItemDetail['itemQuantity'];
                    $remainingQty = $orderedQuantity - $currentReceivedQuantity;

                    if( $newReceivedQuantity == 0)
                    {
                        continue;
                    }

                    $isOverReceived = ($currentReceivedQuantity + $newReceivedQuantity) > $orderedQuantity;
                    $receivedQuantity = $isOverReceived ? $remainingQty : $newReceivedQuantity;
                    $extraReceived = $isOverReceived ? $newReceivedQuantity - $remainingQty : 0;

                    $purchaseOrderItem->current_received_quantity += $receivedQuantity;
                    $purchaseOrderItem->extra_received_quantitty = $extraReceived;
                    $purchaseOrderItem->save();

                            $inventory = Inventory::where('id', $purchaseOrderItem->inventory_id)->first();
                            if ($inventory) {
                                $inventory->quantity += $newReceivedQuantity;
                                $inventory->save();
                            }
                            if ($purchaseOrderItem->current_received_quantity < $orderedQuantity) {
                                $poReceivedFlag = false;
                            }
                            $newPurchaseOrderItem = new PurchaseReceiveLog();
                            $newPurchaseOrderItem->purchase_order_id = $purchaseOrder->id;
                            $newPurchaseOrderItem->purchase_order_item_id = $purchaseOrderItem->id;
                            $newPurchaseOrderItem->remaining_ordered_quantity = $remainingQty;
                            $newPurchaseOrderItem->received_quantity = $receivedQuantity;
                            $newPurchaseOrderItem->extra_quantity = $extraReceived;
                            $newPurchaseOrderItem->save();
                }
                else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'given order item not found',
                ], 400);
            }
        }
            if($poReceivedFlag == true)
            {
                $purchaseOrder->status = 2;
            }
            else{
                $purchaseOrder->status = 1;
            }

            $purchaseOrder->delivery_date = date("Y-m-d");
            $purchaseOrder->order_note = $note;
            $purchaseOrder->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase order quantities successfully updated in inventory.'
            ], 200);
        }
        else {
            return response()->json([
                'status' => 'error',
                'message' => 'purchase order for this id has not found or already received',
            ], 400);
        }

    }
}
