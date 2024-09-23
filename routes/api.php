<?php

use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/get-categories',[ProductController::class,'getCategories']);
Route::post('/add-update-product',[ProductController::class,'addUpdateProduct']);
Route::get('/get-products',[ProductController::class,'getProducts']);
Route::get('/get-product-details/{pid}',[ProductController::class,'getProductDetails']);
