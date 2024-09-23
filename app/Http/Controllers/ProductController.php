<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Request;

class ProductController extends Controller
{
    public function getCategories()
    {
       $category = Category::get();
       return $category;
    }

    public function getProducts()
    {
        $products= Product::get();
        return $products;
    }
    public function getProductDetails($pid)
    {
        $productDetail= Product::with('vendor')->where('id',$pid)->first();
        return $productDetail;
    }

    public function addUpdateProduct(Request $request)
    {
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
            $newProduct = new Product();
            $newProduct->name = $name;
            $newProduct->description = $description;
            $newProduct->serial_num = $batchNum;
            $newProduct->quantity = $quantity;
            $newProduct->price = $price;
            $newProduct->category_id = $categoryId;
            $newProduct->vendor_id = $vendorId;
            return 'product added successfully';
        }
        else{
            $ProductExist = Product::where('id',$productId)->first();
            if($ProductExist)
            {
                $ProductExist->update(['name'=>$name,
                'description'=>$description,
                'quantity'=>$quantity,
                'price'=>$price,
                'category_id'=>$categoryId,
                'vendor_id'=>$vendorId]);

                return 'product updated successfully';
            }
            else{
                return 'product not exist';
            }
        }
    }

public function vendors()
{

}
}
