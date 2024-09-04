<?php

namespace Danielebarbaro\LaravelVatEuValidator;

use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumber;
use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberExist;
use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberFormat;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class VatValidatorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        /**
         * Register the "vat_number" validation rule.
         */
        Validator::extend('vat_number', static function ($attribute, $value, $parameters, $validator): void {
            $rule = new VatNumber();
            $rule->validate($attribute, $value, static fn (string $message = null): null => null);
        });

        /**
         * Register the "vat_number_exist" validation rule.
         */
        Validator::extend('vat_number_exist', static function ($attribute, $value, $parameters, $validator): void {
            $rule = new VatNumberExist();
            $rule->validate($attribute, $value, static fn (string $message = null): null => null);
        });

        /**
         * Register the "vat_number_format" validation rule.
         */
        Validator::extend('vat_number_format', static function ($attribute, $value, $parameters, $validator): void {
            $rule = new VatNumberFormat();
            $rule->validate($attribute, $value, static fn (string $message = null): null => null);
        });

        $this->loadTranslationsFrom(realpath(__DIR__ . '/..').'/resources/lang', 'laravelVatEuValidator');

        $this->publishes([
            realpath(realpath(__DIR__ . '/..').'/resources/lang') => $this->app->langPath('vendor/laravelVatEuValidator'),
        ], 'laravel-vat-eu-validator-lang');
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->app->singleton(VatValidator::class, static fn (Container $app): VatValidator => new VatValidator());
    }
}
