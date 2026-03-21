<?php

use App\Domain\Shop\Http\Controllers\CartController;
use App\Domain\Shop\Http\Controllers\ShopController;
use App\Domain\Shop\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

// Public shop (auth required for cart actions)
Route::get('shop', [ShopController::class, 'index'])->name('shop.index');

Route::middleware(['auth', 'verified'])->group(function () {
    // Cart
    Route::get('cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('cart/items', [CartController::class, 'addItem'])->name('cart.add-item');
    Route::patch('cart/items/{cartItem}', [CartController::class, 'updateItem'])->name('cart.update-item');
    Route::delete('cart/items/{cartItem}', [CartController::class, 'removeItem'])->name('cart.remove-item');
    Route::post('cart/voucher', [CartController::class, 'applyVoucher'])->name('cart.apply-voucher');
    Route::delete('cart/voucher', [CartController::class, 'removeVoucher'])->name('cart.remove-voucher');
    Route::post('cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    Route::get('cart/checkout/{order}/success', [CartController::class, 'checkoutSuccess'])->name('cart.checkout.success');
    Route::get('cart/checkout/{order}/cancel', [CartController::class, 'checkoutCancel'])->name('cart.checkout.cancel');
    Route::get('cart/count', [CartController::class, 'count'])->name('cart.count');

    // Legacy shop routes (kept for voucher validation)
    Route::post('shop/voucher/validate', [ShopController::class, 'validateVoucher'])->name('shop.voucher.validate');

    // Admin: Vouchers
    Route::get('vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('vouchers/create', [VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
    Route::get('vouchers/{voucher}', [VoucherController::class, 'edit'])->name('vouchers.edit');
    Route::patch('vouchers/{voucher}', [VoucherController::class, 'update'])->name('vouchers.update');
    Route::delete('vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
});
