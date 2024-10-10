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
use App\Models\InventoryDetail;
use App\Models\PurchaseReceiveLog;
use App\Models\PurchaseOrderProduct;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\CommonMark\Node\Block\Document;
use PDF;
use File;

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
                $filename = $user->id . '/' . 'PurchaseOrders/' . $newPurchaseOrder->id . '_' . time() . '.pdf';
                $pdf = PDF::loadView('emails.newPurchaseOrder', compact('data'));
                Storage::disk('public')->put($filename, $pdf->output());
                $path = public_path('documents/' . $filename);
                $newPurchaseOrder->update(['po_pdf' => $filename]);
                // try {
                //     Mail::to($checkVendor->email)->send(new SendOrderToVendorMail($path,$data));
                // } catch (\Exception $e) {
                //     Log::error("Mail sending failed: " . $e->getMessage());
                // }
                return response()->customJson('success', 'Purchased order generated successfully', 200);
            } else {
                return response()->customJson('error', 'This Vendor is currently not active', 400);
            }
        } catch (\Exception $e) {
            return response()->customJson('error', $e->getMessage(), 400);
        }
    }

    public function allPurchaseOrders($statusflag, $skey, $sortKey, $sflag, $page, $limit)
    {
        $purchaseData = PurchaseOrder::with('purchaseInventories.inventory', 'vendor', 'purchaseReceiveLogs');
        if ($statusflag == 1) {
            $purchaseData->where('purchase_orders.status', 1);
        } else {
            $purchaseData->where('purchase_orders.status', 2);
        }
        if ($skey != 'null') {
            $purchaseData->where('id', 'like', "%$skey%")
                ->orWhere('total_amount', 'like', "%$skey%")
                ->orWhereHas('vendor', function ($q) use ($skey) {
                    $q->where('name', 'like', "%$skey%");
                });
        }
        if ($sortKey != 'null') {
            if ($sortKey == 'vendorName') {
                $purchaseData->leftJoin('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
                    ->orderBy('vendors.name', $sflag)
                    ->select('purchase_orders.*');
            } else {
                $purchaseData->orderBy("purchase_orders.$sortKey", $sflag);
            }

        } else {
            $purchaseData->orderBy('purchase_orders.id', 'desc');
        }

        return $purchaseData->paginate($limit, ['*'], 'page', $page);
    }


    public function poDetails($pid)
    {
        return PurchaseOrder::with('purchaseInventories.inventory', 'vendor', 'purchaseReceiveLogs')->where('id', $pid)->first();
    }

    public function updateOrderQuantity(Request $request)
    {
        try {
            $poId = $request->input('poId');
            $orderItemDetails = $request->input('orderItemDetails');
            $note = $request->input('note');
            $receipt = $request->input('receipt');


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

                        if ($newReceivedQuantity == 0) {
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

                        $inventoryDetails = InventoryDetail::where('inventory_id', $inventory->id)->first();

                        $inventoryDetails->quantity += $newReceivedQuantity;
                        $inventoryDetails->save();
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
                    } else {
                        return response()->customJson('error', 'Given order item not found', 400);
                    }
                }
                $purchaseOrder->status = $poReceivedFlag ? 2 : 1;

                $user = User::find(1);

                $receiptPath = null;
                if ($receipt != null) {
                    if ($purchaseOrder->receipt != null) {
                        File::delete(public_path('documents/' . $purchaseOrder->receipt));
                    }
                    if (strpos($receipt, 'data:application/pdf;base64,') == 0) {
                        $receipt = substr($receipt, strlen('data:application/pdf;base64,'));
                    }
                    $decodereceipt = base64_decode($receipt);
                    $filename = $user->id . '/' . 'POReceipts/' . $purchaseOrder->id . '_' . time() . '.pdf';
                    Storage::disk('public')->put($filename, $decodereceipt);
                    $path = public_path('documents/' . $filename);
                    $receiptPath = $filename;
                }

                $purchaseOrder->receipt = $receiptPath;
                $purchaseOrder->delivery_date = date("Y-m-d");
                $purchaseOrder->order_note = $note;
                $purchaseOrder->save();

                return response()->customJson('success', 'Purchase order quantities successfully updated in inventory.', 200);
            } else {
                return response()->customJson('error', 'purchase order for this id has not found or already received', 400);
            }

        } catch (\Exception $e) {
            return response()->customJson('error', $e->getMessage(), 400);
        }
    }

    public function recivedPoDetails($poId)
    {
        return PurchaseReceiveLog::with('purchaseOrderItem.inventory')->where('purchase_order_id', $poId)->get();
    }
}
