<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Date;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function getCategories()
    {
       $category = Category::all();
       return $category;
    }

    public function getProducts()
    {
        $products= Product::get();
        return $products;
    }
    public function getProductDetails($pid)
    {
        $productDetail= Product::with('vendor','category')->where('id',$pid)->first();
        return $productDetail;
    }

    public function addUpdateProduct(Request $request)
    {
        try{
        $addUpdateFlag = $request->input('addUpdateFlag');
        $batchNum = $request->input('batchNum');
        $name = $request->input('name');
        $description = $request->input('description');
        $quantity = $request->input('quantity');
        $price = $request->input('price');
        $categoryId = $request->input('categoryId');
        $vendorId =  $request->input('vendorId');
        $productId = $request->input('productId');

        if($addUpdateFlag == 0)
        {
            $checkBatchNumExists = Product::where('batch_num',$batchNum)->first();
            if($checkBatchNumExists)
            {
                return response()->json([
                    'status'=>'error',
                    'message' => 'Batch number is unique for all product',
                ],400);
            }
            $newProduct = new Product();
            $newProduct->name = $name;
            $newProduct->description = $description;
            $newProduct->batch_num = $batchNum;
            $newProduct->quantity = $quantity;
            $newProduct->price = $price;
            $newProduct->category_id = $categoryId;
            $newProduct->vendor_id = $vendorId;
            $newProduct->save();
            return response()->json([
                'status'=>'success',
                'message' => 'Product added successfully',
            ],200);
        }
        else{
            $ProductExists = Product::where('id',$productId)->first();
            if($ProductExists)
            {
                $ProductExists->update(['name'=>$name,
                'description'=>$description,
                'quantity'=>$quantity,
                'price'=>$price,
                'category_id'=>$categoryId,
                'vendor_id'=>$vendorId]);
                return response()->json([
                    'status'=>'success',
                    'message' => 'Product updated successfully',
                ],200);
            }
            else{
                return response()->json([
                    'status'=>'error',
                    'message' => 'Product not Found',
                ],400);
            }
        }
    }
    catch(\Exception $e)
    {
       return response()->json([
        'status'=>'error',
        'message' => $e->getMessage(),
    ],400);
    }
    }
}
