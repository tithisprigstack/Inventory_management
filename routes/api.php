<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\VendorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/get-categories',[ProductController::class,'getCategories']);
Route::post('/add-update-product',[ProductController::class,'addUpdateProduct']);
Route::get('/get-products',[ProductController::class,'getProducts']);
Route::get('/get-product-details/{pid}',[ProductController::class,'getProductDetails']);
Route::get('/get-vendors',[VendorController::class,'getVendors']);
Route::get('/get-vendor-details/{vid}',[VendorController::class,'getVendorDetails']);
Route::post('/delete-product/{pid}',[ProductController::class,'deleteProduct']);
