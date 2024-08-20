<?php

namespace EvanceOdhiambo\MpesaPayment;

use Illuminate\Support\ServiceProvider;

class MpesaPaymentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'mpesa-payment');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'mpesa-payment');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/evance-mpesa.php' => config_path('evance-mpesa.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/mpesa-payment'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/mpesa-payment'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/mpesa-payment'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/evance-mpesa.php', 'mpesa-payment');

        // Register the main class to use with the facade
        $this->app->singleton('mpesa-payment', function () {
            return new MpesaPayment;
        });

        $this->publishes([
            __DIR__ . '/../config/evance-mpesa.php' => config_path('evance-mpesa.php'),
        ], 'config');

        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
    }
}
