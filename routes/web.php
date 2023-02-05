<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('uploads/kyc/{user}/{file}', function (string $file) {
    return Storage::disk('public')->download($file);
})
    ->where(['file' => '\w[0-9a-zA-Z-_.]+'])
    ->name('uploads.download')
    ->middleware('signed');
