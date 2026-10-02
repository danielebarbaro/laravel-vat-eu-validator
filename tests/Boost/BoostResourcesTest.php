<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Boost;

use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Illuminate\Support\Facades\Blade;
use Orchestra\Testbench\TestCase;

class BoostResourcesTest extends TestCase
{
    private const GUIDELINES = __DIR__ . '/../../resources/boost/guidelines/core.blade.php';

    protected function getPackageProviders($app): array
    {
        return [
            VatValidatorServiceProvider::class,
        ];
    }

    private function rendered(): string
    {
        return Blade::render(file_get_contents(self::GUIDELINES));
    }

    public function testShipsASingleCoreGuidelinesFile(): void
    {
        $this->assertFileExists(self::GUIDELINES);
        $this->assertSame(['core.blade.php'], array_values(array_diff(scandir(dirname(self::GUIDELINES)), ['.', '..'])));
    }

    public function testRendersAsBladeWithoutLeavingDirectivesBehind(): void
    {
        $rendered = $this->rendered();

        $this->assertStringNotContainsString('@verbatim', $rendered);
        $this->assertStringNotContainsString('@endverbatim', $rendered);
        $this->assertStringNotContainsString('{{', $rendered);
    }

    public function testNamesTheRulesTheFacadeAndTheClientConfig(): void
    {
        $rendered = $this->rendered();

        foreach (['vat_number', 'vat_number_format', 'vat_number_exist', 'VatValidatorFacade', 'ViesException', 'vat-validator.client'] as $needle) {
            $this->assertStringContainsString($needle, $rendered);
        }
    }

    public function testWrapsItsCodeExamplesInCodeSnippetTags(): void
    {
        $rendered = $this->rendered();

        $this->assertStringContainsString('<code-snippet', $rendered);
        $this->assertStringContainsString('</code-snippet>', $rendered);
        $this->assertSame(substr_count($rendered, '<code-snippet'), substr_count($rendered, '</code-snippet>'));
    }

    public function testKeepsTheGuidelinesShortEnoughToStayInContext(): void
    {
        $this->assertLessThan(6000, strlen($this->rendered()));
    }

    public function testSuggestsLaravelBoostWithoutRequiringIt(): void
    {
        $composer = json_decode(file_get_contents(__DIR__ . '/../../composer.json'), true);

        $this->assertIsString($composer['suggest']['laravel/boost'] ?? null);
        $this->assertNotSame('', $composer['suggest']['laravel/boost']);
        $this->assertArrayNotHasKey('laravel/boost', $composer['require'] ?? []);
        $this->assertArrayNotHasKey('laravel/boost', $composer['require-dev'] ?? []);
    }
}
