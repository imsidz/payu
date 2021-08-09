<?php

use Illuminate\Support\Facades\Route;
use Imsidz\Payu\Controllers\StatusController;

Route::post('vendor-payu/status', StatusController::class)->name('payu::redirect');
