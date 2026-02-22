<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\RewardController;
use App\Http\Controllers\AMLevelUpController;
use App\Http\Controllers\BackController;



Route::get('/', [AMLevelUpController::class, 'getReportData'])->name('home');
Route::get('/am-level-up/input', [AMLevelUpController::class, 'index'])->name('amlevelup.index');
Route::get('/am-level-up/report', [AMLevelUpController::class, 'report'])->name('amlevelup.report');

Route::post('/login', [BackController::class, 'login'])->name('login');
Route::post('/logout', [BackController::class, 'logout'])->name('logout');

Route::post('/redeem', [AMLevelUpController::class, 'redeemPrize'])->name('redeem');
