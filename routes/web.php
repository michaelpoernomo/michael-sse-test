<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\StoreController;

Route::get('/', [StoreController::class, 'schedule']);
Route::get('/parse-data', [StoreController::class, 'parseStoreData']);