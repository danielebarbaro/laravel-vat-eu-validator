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
        Validator::extend('vat_number', static function ($attribute, $value, $parameters, $validator): bool {
            $rule = new VatNumber();
            return $rule->passes($attribute, $value);
        });

        /**
         * Register the "vat_number_exist" validation rule.
         */
        Validator::extend('vat_number_exist', static function ($attribute, $value, $parameters, $validator): bool {
            $rule = new VatNumberExist();
            return $rule->passes($attribute, $value);
        });

        /**
         * Register the "vat_number_format" validation rule.
         */
        Validator::extend('vat_number_format', static function ($attribute, $value, $parameters, $validator): bool {
            $rule = new VatNumberFormat();
            return $rule->passes($attribute, $value);
        });
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->app->singleton(VatValidator::class, static fn (Container $app): \Danielebarbaro\LaravelVatEuValidator\VatValidator => new VatValidator());
    }
}
