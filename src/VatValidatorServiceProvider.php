<?php

namespace Danielebarbaro\LaravelVatEuValidator;

use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumber;
use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberExist;
use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberFormat;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class VatValidatorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /**
         * Register the "vat_number" validation rule.
         */
        Validator::extend('vat_number', function ($attribute, $value, $parameters, $validator) {
            $rule = new VatNumber();
            return $rule->passes($attribute, $value);
        });

        /**
         * Register the "vat_number_exist" validation rule.
         */
        Validator::extend('vat_number_exist', function ($attribute, $value, $parameters, $validator) {
            $rule = new VatNumberExist();
            return $rule->passes($attribute, $value);
        });

        /**
         * Register the "vat_number_format" validation rule.
         */
        Validator::extend('vat_number_format', function ($attribute, $value, $parameters, $validator) {
            $rule = new VatNumberFormat();
            return $rule->passes($attribute, $value);
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton(VatValidator::class, function () {
            return new VatValidator();
        });
    }
}
