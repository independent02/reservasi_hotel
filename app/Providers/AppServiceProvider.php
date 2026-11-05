<?php

namespace App\Providers;

use App\Models\Faq;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        View::composer('partials.help', function ($view): void {
            $locale = app()->getLocale();
            $view->with('helpFaqs', Cache::remember(
                'faqs_help_'.$locale,
                now()->addHour(),
                fn () => Faq::active()->get(),
            ));
        });
    }
}
