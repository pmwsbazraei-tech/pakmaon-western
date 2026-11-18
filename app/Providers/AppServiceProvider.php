<?php

namespace App\Providers;

use App\Http\Middleware\EnsureOutlet;
use App\Services\Payments\ChipGateway;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\PaymentGateway;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Gateway Bayar Online: CHIP Collect, atau simulasi untuk local/testing sahaja
        $this->app->singleton(PaymentGateway::class, function ($app) {
            if (config('services.payment_driver') === 'chip') {
                $c = config('services.chip');
                if (! $c['brand_id'] || ! $c['secret_key']) {
                    throw new \RuntimeException('CHIP_BRAND_ID dan CHIP_SECRET_KEY belum ditetapkan dalam .env');
                }

                return new ChipGateway($c['brand_id'], $c['secret_key'], $c['webhook_public_key']);
            }

            if (! $app->environment(['local', 'testing'])) {
                throw new \RuntimeException('Gateway simulasi hanya untuk local/testing. Tetapkan PAYMENT_DRIVER=chip.');
            }

            return new FakeGateway;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // @rm($amount) → RM1,234.56
        Blade::directive('rm', fn ($expression) => "<?php echo rm($expression); ?>");

        // @sen($cents) → RM1,234.56 (amaun tempahan disimpan dalam sen)
        Blade::directive('sen', fn ($expression) => "<?php echo rm(($expression) / 100); ?>");

        Carbon::setLocale('ms');

        // Jalankan semula EnsureOutlet pada setiap kemas kini Livewire (/livewire/update),
        // supaya $outlet yang dikongsi ke view wujud selepas tukar bulan / tapis.
        // Tanpa ini, wire:model.live="month" di Dashboard menghasilkan "Undefined variable $outlet".
        Livewire::addPersistentMiddleware([EnsureOutlet::class]);
    }
}
