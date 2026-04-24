<?php

namespace App\Domain\Shop\Http\Controllers;

use App\Domain\Shop\Enums\Currency;
use App\Domain\Shop\Http\Requests\UpdateShopCurrencyRequest;
use App\Domain\Shop\Models\ShopSetting;
use App\Domain\Shop\PaymentProviders\PaymentProviderManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopSettingsController extends Controller
{
    public function __construct(private readonly PaymentProviderManager $providerManager) {}

    public function index(): Response
    {
        return Inertia::render('shop/Settings', [
            'paymentMethods' => $this->providerManager->allMethods(),
            'invoiceConfig' => [
                'invoice_prefix' => ShopSetting::get('invoice_prefix', 'INV-'),
                'invoice_footer' => ShopSetting::get('invoice_footer', ''),
                'invoice_notes' => ShopSetting::get('invoice_notes', ''),
            ],
            'currency' => ShopSetting::currency(),
            'availableCurrencies' => array_map(
                fn (Currency $currency): array => [
                    'value' => $currency->value,
                    'label' => $currency->label(),
                    'symbol' => $currency->symbol(),
                ],
                Currency::cases(),
            ),
        ]);
    }

    public function updatePaymentMethods(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'methods' => ['required', 'array'],
            'methods.*' => ['boolean'],
        ]);

        ShopSetting::set('enabled_payment_methods', $validated['methods']);

        return back();
    }

    public function updateInvoiceConfig(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'invoice_footer' => ['nullable', 'string', 'max:2000'],
            'invoice_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach ($validated as $key => $value) {
            ShopSetting::set($key, $value ?? '');
        }

        return back();
    }

    public function updateCurrency(UpdateShopCurrencyRequest $request): RedirectResponse
    {
        ShopSetting::setCurrency($request->string('currency')->value());

        return back();
    }
}
