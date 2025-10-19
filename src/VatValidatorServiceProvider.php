<?php

namespace Danielebarbaro\LaravelVatEuValidator;

use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumber;
use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberExist;
use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberFormat;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesClientInterface;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesRestClient;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesSoapClient;
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
        Validator::extend(
            'vat_number',
            static function ($attribute, $value, $parameters, $validator): bool {
                $passed = true;
                $rule = new VatNumber();

                $rule->validate(
                    $attribute,
                    $value,
                    static function (?string $message = null) use (&$passed): void {
                        $passed = false;
                    }
                );

                return $passed;
            }
        );

        Validator::replacer(
            'vat_number',
            fn (string $message, string $attribute, string $rule, array $parameters): string =>
            $message === 'validation.vat_number' ? __("laravelVatEuValidator::{$message}", ['attribute' => $attribute]) : $message
        );

        /**
         * Register the "vat_number_exist" validation rule.
         */
        Validator::extend(
            'vat_number_exist',
            static function ($attribute, $value, $parameters, $validator): bool {
                $passed = true;
                $rule = new VatNumberExist();

                $rule->validate(
                    $attribute,
                    $value,
                    static function (?string $message = null) use (&$passed): void {
                        $passed = false;
                    }
                );

                return $passed;
            }
        );

        Validator::replacer(
            'vat_number_exist',
            fn (string $message, string $attribute, string $rule, array $parameters): string =>
            $message === 'validation.vat_number_exist' ? __("laravelVatEuValidator::{$message}", ['attribute' => $attribute]) : $message
        );

        /**
         * Register the "vat_number_format" validation rule.
         */
        Validator::extend(
            'vat_number_format',
            static function ($attribute, $value, $parameters, $validator): bool {
                $passed = true;
                $rule = new VatNumberFormat();

                $rule->validate(
                    $attribute,
                    $value,
                    static function (?string $message = null) use (&$passed): void {
                        $passed = false;
                    }
                );

                return $passed;
            }
        );

        Validator::replacer(
            'vat_number_format',
            fn (string $message, string $attribute, string $rule, array $parameters): string =>
            $message === 'validation.vat_number_format' ? __("laravelVatEuValidator::{$message}", ['attribute' => $attribute]) : $message
        );

        $this->loadTranslationsFrom(
            __DIR__.'/../resources/lang',
            'laravelVatEuValidator'
        );

        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/laravelVatEuValidator'),
        ], 'laravel-vat-eu-validator-lang');

        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/vat-validator.php' => config_path('vat-validator.php'),
        ], 'laravel-vat-eu-validator-config');
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        // Merge package configuration with application configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/vat-validator.php',
            'vat-validator'
        );

        // Register individual VIES clients
        $this->app->bind('vies.soap', function (Container $app): ViesSoapClient {
            $timeout = config('vat-validator.clients.' . ViesSoapClient::CLIENT_NAME . '.timeout', 10);

            return new ViesSoapClient($timeout);
        });

        $this->app->bind('vies.rest', function (Container $app): ViesRestClient {
            $apiKeyId = config('vat-validator.clients.' . ViesRestClient::CLIENT_NAME . '.api_key_id');
            $apiKey = config('vat-validator.clients.' . ViesRestClient::CLIENT_NAME . '.api_key');
            $baseUrl = config('vat-validator.clients.' . ViesRestClient::CLIENT_NAME . '.base_url', ViesRestClient::BASE_URL);
            $timeout = config('vat-validator.clients.' . ViesRestClient::CLIENT_NAME . '.timeout', 10);

            return new ViesRestClient($apiKeyId, $apiKey, $baseUrl, $timeout);
        });

        // Register the VIES client interface based on configuration
        $this->app->bind(
            ViesClientInterface::class,
            function (Container $app): ViesClientInterface {
                $clientType = config('vat-validator.client', ViesSoapClient::CLIENT_NAME);

                return match ($clientType) {
                    ViesSoapClient::CLIENT_NAME => $app->make('vies.soap'),
                    ViesRestClient::CLIENT_NAME => $app->make('vies.rest'),
                    default => throw new \InvalidArgumentException("Unknown VIES client type: {$clientType}"),
                };
            }
        );

        $this->app->singleton(
            VatValidator::class,
            static fn (Container $app): VatValidator => new VatValidator(
                $app->make(ViesClientInterface::class)
            )
        );
    }
}
