<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\RewardController;
use App\Http\Controllers\AMLevelUpController;
use App\Http\Controllers\B2BPortalController;
use App\Http\Controllers\BackController;



Route::get('/', [AMLevelUpController::class, 'getReportData'])->name('home');
Route::get('/am-level-up/input', [AMLevelUpController::class, 'index'])->name('amlevelup.index');
Route::get('/am-level-up/report', [AMLevelUpController::class, 'report'])->name('amlevelup.report');

Route::post('/am-level-up/store', [AMLevelUpController::class, 'store'])->name('amlevelup.store');

Route::post('/login', [BackController::class, 'login'])->name('login');
Route::post('/logout', [BackController::class, 'logout'])->name('logout');

Route::post('/redeem', [AMLevelUpController::class, 'redeemPrize'])->name('redeem');

Route::middleware('b2b')->group(function () {
    Route::get('/b2b/input-klien', [B2BPortalController::class, 'inputClient'])->name('b2b.clients.index');
    Route::post('/b2b/input-klien', [B2BPortalController::class, 'storeClient'])->name('b2b.clients.store');
    Route::get('/b2b/performansi', [B2BPortalController::class, 'performance'])->name('b2b.performance');
    Route::get('/b2b/leaderboard', [B2BPortalController::class, 'leaderboard'])->name('b2b.leaderboard');
    Route::get('/b2b/reward', [B2BPortalController::class, 'rewards'])->name('b2b.rewards');
    Route::post('/b2b/reward/redeem', [B2BPortalController::class, 'redeemReward'])->name('b2b.redeem');
});
