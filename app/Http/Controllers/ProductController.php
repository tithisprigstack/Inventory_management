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
      return Category::all();
    }

    public function getProducts()
    {
        return Product::with(['vendor' => function($query) {
            $query->withTrashed();
        }])->get();
    }
    public function getProductDetails($pid)
    {
        return Product::with('vendor', 'category')->where('id', $pid)->first();
    }

    public function addUpdateProduct(Request $request)
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
            $productId = $request->input('productId');

            if ($addUpdateFlag == 0) {
                $checkSkuExists = Product::where('sku', $sku)->first();
                if ($checkSkuExists) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'SKU is unique for all product',
                    ], 400);
                }
                $newProduct = new Product();
                $newProduct->name = $name;
                $newProduct->description = $description;
                $newProduct->sku = $sku;
                $newProduct->quantity = $quantity;
                $newProduct->price = $price;
                $newProduct->category_id = $categoryId;
                $newProduct->vendor_id = $vendorId;
                $newProduct->save();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Product added successfully',
                ], 200);
            } else {
                $ProductExists = Product::where('id', $productId)->first();
                if ($ProductExists) {
                    $vendor = Vendor::find($vendorId);
                    if (!$vendor ) {
                        return response()->json([
                            'status' => 'error',
                        'message' => 'vendor is not Found'], 400);
                    }
                    $ProductExists->update([
                        'name' => $name,
                        'description' => $description,
                        'sku' => $sku,
                        'quantity' => $quantity,
                        'price' => $price,
                        'category_id' => $categoryId,
                        'vendor_id' => $vendorId
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Product updated successfully',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Product not Found',
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

    public function deleteProduct($pid)
    {
        try {
            Product::where('id', $pid)->delete();
            return 'success';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
