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
        $inventoryDetails = $request->input('inventoryDetails');
        $vendorId =  $request->input('vendorId');

    $checkVendor = Vendor::where('id',$vendorId)->where('status',1)->first();
        if ($checkVendor) {
                $newPurchaseOrder = new PurchaseOrder();
                $newPurchaseOrder->vendor_id = $vendorId;
                $newPurchaseOrder->status = 1;
                $newPurchaseOrder->save();

                $total_amount = 0;
                $inventoryData = [];
                foreach($inventoryDetails as $inventoryDetail)
                {
                    $inventory = Inventory::with('category')->where('id',$inventoryDetail['inventoryId'])->first();
                    $newPurchaseOrderItem = new PurchaseOrderItem();
                    $newPurchaseOrderItem->purchase_order_id = $newPurchaseOrder->id;
                    $newPurchaseOrderItem->inventory_id = $inventoryDetail['inventoryId'];
                    $newPurchaseOrderItem->quantity =  $inventoryDetail['reminderQuantity'];
                    $newPurchaseOrderItem->price =  $inventoryDetail['price'];
                    $newPurchaseOrderItem->save();
                    $total_amount += $inventoryDetail['reminderQuantity'] * $inventoryDetail['price'];
                    $inventoryData[] = ['inventory' => $inventory,'poItemDetails' => $newPurchaseOrderItem];
                }
                $newPurchaseOrder->update(['total_amount' => $total_amount]);
                $data = [
                    'vendorDetails' => $checkVendor,
                    'inventoryDetails'=>$inventoryData,
                ];
                $filename = 'Orders/' . $newPurchaseOrder->id . '_' . time() . '.pdf';
                $pdf = PDF::loadView('emails.newPurchaseOrder', compact('data'));
                Storage::disk('public')->put($filename, $pdf->output());
                $path = public_path('documents/'.$filename);
                $newPurchaseOrder->update(['po_pdf' => $filename]);
                try {
                    Mail::to($checkVendor->email)->send(new SendOrderToVendorMail($path,$data));
                } catch (\Exception $e) {
                    Log::error("Mail sending failed: " . $e->getMessage());
                }
                return response()->json([
                    'status' => 'success',
                    'message' => 'Purchased order generated successfully',
                ], 200);
            }
            else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'This Vendor is currently not active',
                ], 400);
            }
    }

    // public function allPurchaseOrders($skey,$sortKey,$sflag,$page,$limit)
    // {
    //     $purchaseData = PurchaseOrder::with('purchaseInventories.inventory','vendor');

    //     if($skey != 'null')
    //     {
    //         $purchaseData->where('id','like',"%$skey%")->orWhere('order_date','like',"%$skey%");
    //     }
    //     return $purchaseData->get();
    // }

    // public function poDetails($pid)
    // {
    //    return PurchaseOrder::with('purchaseInventories.inventory','vendor')->where('id', $pid)->first();
    // }

    public function updateOrder(Request $request)
    {
        $inventoryId = $request->input('inventoryId');
        $quantity = $request->input('quantity');

        $purchaseOrder = PurchaseOrderItem::with('purchaseOrder')->where('inventory_id',$inventoryId)->whereHas('purchaseOrder',function ($q){
            $q->where('status',1);
        });


        return $purchaseOrder->get();
    }
}
