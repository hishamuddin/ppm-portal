<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('portal.*', function ($view) {
            $locale = $view->getData()['locale'] ?? 'ms';
            $settings = Setting::read('portal', []);
            $view->with(['ms' => $locale === 'ms', 'settings' => $settings, 'siteTitle' => $settings['title_'.$locale] ?? ($locale === 'ms' ? 'Pejabat Perdana Menteri' : 'Prime Minister’s Office')]);
        });
    }
}
