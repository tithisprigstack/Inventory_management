<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VendorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/get-categories',[InventoryController::class,'getCategories']);
Route::post('/add-update-inventory',[InventoryController::class,'addUpdateInventory']);
Route::get('/all-inventories/{skey}&{sortkey}&{sflag}&{page}&{limit}',[InventoryController::class,'allInventories']);
Route::get('/get-inventory-details/{id}',[InventoryController::class,'getInventoryDetails']);
Route::get('/get-vendors/{skey}&{sortkey}&{sflag}&{page}&{limit}',[VendorController::class,'getVendors']);
Route::get('/get-vendor-details/{vid}',[VendorController::class,'getVendorDetails']);
Route::post('/delete-inventory/{id}',[InventoryController::class,'deleteInventory']);
Route::post('/add-update-vendor',[VendorController::class,'addUpdateVendor']);
Route::post('/delete-vendor/{vid}',[VendorController::class,'deleteVendor']);

// Route::post('/generate-purchase-order',[OrderController::class,'generatePurchaseOrder']);
