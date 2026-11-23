<?php

use App\Http\Controllers\Shop;
use App\Http\Controllers\TempahanExportController;
use App\Http\Middleware\SetLocale;
use App\Livewire\Actions\Logout;
use App\Livewire\CapexBudget;
use App\Livewire\Dashboard;
use App\Livewire\Equipment;
use App\Livewire\FixedCosts;
use App\Livewire\FullReport;
use App\Livewire\MenuFoodCost;
use App\Livewire\PettyCash;
use App\Livewire\RoiTarget;
use App\Livewire\SalesManager;
use App\Livewire\ScanReceipt;
use App\Livewire\Settings;
use App\Livewire\StaffManager;
use App\Livewire\Tempahan;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'outlet'])->group(function () {
    // Modul kewangan lama: disembunyikan dari menu, laluan dikekalkan
    Route::get('kewangan', Dashboard::class)->name('finance');
    Route::get('scan', ScanReceipt::class)->name('scan');
    Route::get('staff', StaffManager::class)->name('staff');
    Route::get('kos-tetap', FixedCosts::class)->name('fixed-costs');
    Route::get('sales', SalesManager::class)->name('sales');
    Route::get('petty-cash', PettyCash::class)->name('petty-cash');
    Route::get('menu', MenuFoodCost::class)->name('menu');
    Route::get('peralatan', Equipment::class)->name('equipment');
    Route::get('bajet', CapexBudget::class)->name('capex');
    Route::get('roi', RoiTarget::class)->name('roi');
    Route::get('laporan', FullReport::class)->name('report');
    Route::get('tetapan', Settings::class)->name('settings');
});

// Dashboard utama: tempahan online ikut cawangan (pemilik + staf cawangan)
Route::get('dashboard', Tempahan\BranchDashboard::class)->middleware(['auth', 'outlet:staff'])->name('dashboard');

// Tukar bahasa paparan (BM / EN)
Route::get('bahasa/{locale}', function (string $locale) {
    session(['locale' => $locale]);

    return redirect()->back(fallback: route('dashboard'));
})->whereIn('locale', array_keys(SetLocale::LOCALES))->name('locale.switch');

// Log keluar: semua user log masuk, termasuk staf cawangan
Route::post('logout', function (Logout $logout) {
    $logout();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Tempahan online: backend (pemilik + staf cawangan)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'outlet:staff'])->prefix('tempahan')->name('tempahan.')->group(function () {
    Route::get('/', Tempahan\Overview::class)->name('overview');
    Route::get('booking-harian', Tempahan\DailyBookings::class)->name('bookings');
    Route::get('pesanan', Tempahan\Orders::class)->name('orders');
    Route::get('pesanan/eksport', TempahanExportController::class)->name('orders.export');
    Route::get('pesanan/{order}', Tempahan\OrderDetail::class)->name('orders.show');

    Route::middleware('shop.manage')->group(function () {
        Route::get('landing-page', Tempahan\Forms::class)->name('forms');
        Route::get('landing-page/baharu', Tempahan\FormEditor::class)->name('forms.create');
        Route::get('landing-page/{form}', Tempahan\FormEditor::class)->name('forms.edit');
        Route::get('produk', Tempahan\Products::class)->name('products');
        Route::get('produk/baharu', Tempahan\ProductEditor::class)->name('products.create');
        Route::get('produk/{product}', Tempahan\ProductEditor::class)->name('products.edit');
        Route::get('outlet', Tempahan\OutletSettings::class)->name('outlets');
        Route::get('staf', Tempahan\StaffAccounts::class)->name('staff');
    });
});

/*
|--------------------------------------------------------------------------
| Tempahan online: landing page awam (tanpa login)
|--------------------------------------------------------------------------
*/
Route::get('p/{slug}', [Shop\FormController::class, 'show'])->name('form.show');
Route::post('p/{slug}/quote', [Shop\FormController::class, 'quote'])->middleware('throttle:120,1')->name('form.quote');
Route::get('p/{slug}/slots', [Shop\FormController::class, 'slots'])->middleware('throttle:120,1')->name('form.slots');
Route::post('p/{slug}/orders', [Shop\FormController::class, 'store'])->middleware('throttle:20,1')->name('form.store');

// Status pesanan pelanggan (dilindungi token rawak dalam URL)
Route::get('o/{reference}', [Shop\OrderController::class, 'show'])->name('order.show');
Route::get('o/{reference}/whatsapp', [Shop\OrderController::class, 'whatsapp'])->name('order.whatsapp');
Route::post('o/{reference}/pay', [Shop\OrderController::class, 'pay'])->middleware('throttle:20,1')->name('order.pay');

// Gateway
Route::post('payments/chip/callback', [Shop\PaymentCallbackController::class, 'callback'])->name('payments.chip.callback');
Route::post('payments/chip/webhook', [Shop\PaymentCallbackController::class, 'webhook'])->name('payments.chip.webhook');
Route::get('pay/simulasi/{id}', [Shop\OrderController::class, 'fakeGateway'])->name('payments.fake.show');
Route::post('pay/simulasi/{id}', [Shop\OrderController::class, 'fakeGatewayComplete'])->name('payments.fake.complete');

require __DIR__.'/auth.php';
