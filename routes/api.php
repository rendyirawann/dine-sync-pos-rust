<?php

use App\Http\Controllers\Backend\Kasir\KasirController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\Kasir\MidtransWebhookController;

Route::post('/midtrans-webhook', [KasirController::class, 'handleWebhook']);
