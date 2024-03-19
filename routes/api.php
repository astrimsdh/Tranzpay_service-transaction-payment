<?php

use App\Http\Controllers\DigiflazController;
use App\Http\Controllers\TopupController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('topups', [TopupController::class, 'create']);
Route::get('topups', [TopupController::class, 'index']);

Route::post('webhook-topup', [WebhookController::class, 'midtransHandler']);

Route::post('get-product-prepaid', [DigiflazController::class, 'get_product_prepaid']);
Route::post('get-product-pasca', [DigiflazController::class, 'get_product_pasca']);

Route::post('transactions', [DigiflazController::class, 'digiflazTopup']);
Route::get('transactions', [TransactionController::class, 'index']);
Route::get('transactions/{id}', [TransactionController::class, 'show']);

Route::post('cek-tagihan', [DigiflazController::class, 'digiflazCekTagihan']);
Route::post('bayar-tagihan', [DigiflazController::class, 'digiflazBayarTagihan']);
Route::post('cek-saldo', [DigiflazController::class, 'cekSaldoUser']);
Route::post('cek-id-pln', [DigiflazController::class, 'cekIDPLN']);
Route::post('deposit', [DigiflazController::class, 'depositDigiflaz']);

Route::post('/webhook-digiflaz', [WebhookController::class, 'digiflazHandler']);
