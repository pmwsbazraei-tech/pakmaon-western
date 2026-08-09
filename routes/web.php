<?php

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
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'outlet'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
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

    Route::post('logout', function (Logout $logout) {
        $logout();

        return redirect()->route('login');
    })->name('logout');
});

require __DIR__.'/auth.php';
