<?php

use App\Domain\Shop\Http\Controllers\CartController;
use App\Domain\Shop\Http\Controllers\GlobalPurchaseConditionController;
use App\Domain\Shop\Http\Controllers\OrderController;
use App\Domain\Shop\Http\Controllers\PaymentProviderConditionController;
use App\Domain\Shop\Http\Controllers\PurchaseRequirementController;
use App\Domain\Shop\Http\Controllers\ShopController;
use App\Domain\Shop\Http\Controllers\ShopSettingsController;
use App\Domain\Shop\Http\Controllers\UserOrderController;
use App\Domain\Shop\Http\Controllers\VoucherAuditController;
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
    Route::post('cart/review', [CartController::class, 'reviewCheckout'])->name('cart.review-checkout');
    Route::post('cart/acknowledge', [CartController::class, 'acknowledge'])->name('cart.acknowledge');
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
    Route::get('vouchers/{voucher}/audit', VoucherAuditController::class)->name('vouchers.audit');
    Route::patch('vouchers/{voucher}', [VoucherController::class, 'update'])->name('vouchers.update');
    Route::delete('vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');

    // Admin: Purchase Requirements
    Route::get('purchase-requirements', [PurchaseRequirementController::class, 'index'])->name('purchase-requirements.index');
    Route::get('purchase-requirements/create', [PurchaseRequirementController::class, 'create'])->name('purchase-requirements.create');
    Route::post('purchase-requirements', [PurchaseRequirementController::class, 'store'])->name('purchase-requirements.store');
    Route::get('purchase-requirements/{purchaseRequirement}', [PurchaseRequirementController::class, 'edit'])->name('purchase-requirements.edit');
    Route::patch('purchase-requirements/{purchaseRequirement}', [PurchaseRequirementController::class, 'update'])->name('purchase-requirements.update');
    Route::delete('purchase-requirements/{purchaseRequirement}', [PurchaseRequirementController::class, 'destroy'])->name('purchase-requirements.destroy');

    // Admin: Global Purchase Conditions
    Route::get('global-purchase-conditions', [GlobalPurchaseConditionController::class, 'index'])->name('global-purchase-conditions.index');
    Route::get('global-purchase-conditions/create', [GlobalPurchaseConditionController::class, 'create'])->name('global-purchase-conditions.create');
    Route::post('global-purchase-conditions', [GlobalPurchaseConditionController::class, 'store'])->name('global-purchase-conditions.store');
    Route::get('global-purchase-conditions/{globalPurchaseCondition}', [GlobalPurchaseConditionController::class, 'edit'])->name('global-purchase-conditions.edit');
    Route::patch('global-purchase-conditions/{globalPurchaseCondition}', [GlobalPurchaseConditionController::class, 'update'])->name('global-purchase-conditions.update');
    Route::delete('global-purchase-conditions/{globalPurchaseCondition}', [GlobalPurchaseConditionController::class, 'destroy'])->name('global-purchase-conditions.destroy');

    // Admin: Shop Settings
    Route::get('shop-settings', [ShopSettingsController::class, 'index'])->name('shop-settings.index');
    Route::patch('shop-settings/payment-methods', [ShopSettingsController::class, 'updatePaymentMethods'])->name('shop-settings.update-payment-methods');
    Route::patch('shop-settings/invoice', [ShopSettingsController::class, 'updateInvoiceConfig'])->name('shop-settings.update-invoice');
    Route::patch('shop-settings/currency', [ShopSettingsController::class, 'updateCurrency'])->name('shop-settings.update-currency');

    // Admin: Payment Provider Conditions
    Route::get('payment-provider-conditions', [PaymentProviderConditionController::class, 'index'])->name('payment-provider-conditions.index');
    Route::get('payment-provider-conditions/create', [PaymentProviderConditionController::class, 'create'])->name('payment-provider-conditions.create');
    Route::post('payment-provider-conditions', [PaymentProviderConditionController::class, 'store'])->name('payment-provider-conditions.store');
    Route::get('payment-provider-conditions/{paymentProviderCondition}', [PaymentProviderConditionController::class, 'edit'])->name('payment-provider-conditions.edit');
    Route::patch('payment-provider-conditions/{paymentProviderCondition}', [PaymentProviderConditionController::class, 'update'])->name('payment-provider-conditions.update');
    Route::delete('payment-provider-conditions/{paymentProviderCondition}', [PaymentProviderConditionController::class, 'destroy'])->name('payment-provider-conditions.destroy');

    // My Orders (user-facing)
    Route::get('my-orders', [UserOrderController::class, 'index'])->name('my-orders.index');
    Route::get('my-orders/{order}', [UserOrderController::class, 'show'])->name('my-orders.show');

    // Admin: Orders
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/confirm-payment', [OrderController::class, 'confirmPayment'])->name('orders.confirm-payment');
    Route::get('orders/{order}/invoice', [OrderController::class, 'downloadInvoice'])->name('orders.download-invoice');
    Route::get('orders/{order}/receipt', [OrderController::class, 'downloadReceipt'])->name('orders.download-receipt');
});
