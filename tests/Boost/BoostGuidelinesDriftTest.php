<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Boost;

use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase;

/**
 * Fails when the Boost guidelines mention a class, method, constant, config
 * key, env variable, validation rule, translation key or publish tag that the
 * package no longer provides. Identifiers are extracted from the rendered
 * guidelines, so a new mention is checked without touching this test.
 */
class BoostGuidelinesDriftTest extends TestCase
{
    private const GUIDELINES = __DIR__ . '/../../resources/boost/guidelines/core.blade.php';

    private const PACKAGE_ROOT = __DIR__ . '/../..';

    private const PACKAGE_NAMESPACE = 'Danielebarbaro\\LaravelVatEuValidator';

    /**
     * Framework classes the guidelines may reference by short name.
     */
    private const FRAMEWORK_CLASSES = [
        'Http' => \Illuminate\Support\Facades\Http::class,
    ];

    /**
     * Instance methods called in code snippets that belong to the framework
     * or to Mockery, not to this package.
     */
    private const FRAMEWORK_METHODS = ['andReturn'];

    private string $rendered;

    protected function getPackageProviders($app): array
    {
        return [
            VatValidatorServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->rendered = Blade::render(file_get_contents(self::GUIDELINES));
    }

    public function testEveryPackageClassMentionedExists(): void
    {
        $classes = $this->extract('/' . preg_quote(self::PACKAGE_NAMESPACE, '/') . '(?:\\\\\w+)+/');

        $this->assertGreaterThanOrEqual(5, count($classes), 'Extraction found too few package classes');

        foreach ($classes as $class) {
            $this->assertTrue(
                class_exists($class) || interface_exists($class),
                "Guidelines mention {$class}, which does not exist"
            );
        }
    }

    public function testEveryStaticReferenceResolvesToARealMethodOrConstant(): void
    {
        $aliases = $this->classAliases();
        preg_match_all('/\b([A-Z]\w*)::([A-Za-z_]\w*)/', $this->rendered, $found, PREG_SET_ORDER);

        $this->assertGreaterThanOrEqual(5, count($found), 'Extraction found too few Class::member references');

        foreach ($found as [$reference, $short, $member]) {
            $this->assertArrayHasKey($short, $aliases, "Guidelines reference {$reference}, but {$short} is not a known class");

            if ($member === 'class') {
                continue;
            }

            $class = $aliases[$short];

            if (strtoupper($member) === $member) {
                $this->assertTrue(defined("{$class}::{$member}"), "Guidelines reference {$reference}, but {$class}::{$member} is not defined");

                continue;
            }

            $this->assertTrue(
                $this->hasMethod($class, $member),
                "Guidelines reference {$reference}, but {$class} has no method {$member}"
            );
        }
    }

    public function testEveryCalledMethodExistsOnAPackageClass(): void
    {
        $calls = array_merge(
            $this->extract('/->(\w+)\(/', 1),
            $this->extract('/`(\w+)\(\)`/', 1),
        );

        $this->assertNotEmpty($calls, 'Extraction found no method calls');

        foreach (array_unique($calls) as $method) {
            if (in_array($method, self::FRAMEWORK_METHODS, true)) {
                continue;
            }

            $owners = array_filter($this->packageClasses(), fn (string $class): bool => method_exists($class, $method));

            $this->assertNotEmpty($owners, "Guidelines call {$method}(), which no package class defines");
        }
    }

    public function testEveryConfigKeyMentionedExistsInThePackageConfig(): void
    {
        $config = require self::PACKAGE_ROOT . '/config/vat-validator.php';
        $keys = $this->extract('/(?<![\w\/])vat-validator\.([a-z_]+(?:\.[a-z_]+)*)/', 1);

        $this->assertGreaterThanOrEqual(3, count($keys), 'Extraction found too few config keys');

        foreach ($keys as $key) {
            $this->assertTrue(Arr::has($config, $key), "Guidelines mention config key vat-validator.{$key}, which the package config does not define");
            $this->assertNotNull(config("vat-validator.{$key}"), "Config key vat-validator.{$key} is not merged by the service provider");
        }

        foreach ($this->extract('/config\/[\w-]+\.php/') as $file) {
            $this->assertFileExists(self::PACKAGE_ROOT . '/' . $file);
        }
    }

    public function testEveryEnvVariableMentionedIsReadByThePackageConfig(): void
    {
        $source = file_get_contents(self::PACKAGE_ROOT . '/config/vat-validator.php');
        $variables = $this->extract('/\bVIES_[A-Z_]+\b/');

        $this->assertNotEmpty($variables, 'Extraction found no env variables');

        foreach ($variables as $variable) {
            $this->assertStringContainsString("env('{$variable}'", $source, "Guidelines mention {$variable}, which the package config does not read");
        }
    }

    public function testEveryValidationRuleNameMentionedIsRegistered(): void
    {
        $extensions = Validator::make([], [])->extensions;
        $rules = $this->extract('/\bvat_number(?:_[a-z]+)*\b/');

        $this->assertGreaterThanOrEqual(3, count($rules), 'Extraction found too few rule names');

        foreach ($rules as $rule) {
            $this->assertArrayHasKey($rule, $extensions, "Guidelines mention the {$rule} rule, which is not registered");
        }
    }

    public function testEveryTranslationKeyMentionedExists(): void
    {
        $keys = $this->extract('/laravelVatEuValidator::[\w.]*\w/');

        $this->assertNotEmpty($keys, 'Extraction found no translation keys');

        foreach ($keys as $key) {
            $this->assertTrue(Lang::has($key, 'en', false), "Guidelines mention translation key {$key}, which does not exist");
        }
    }

    public function testEveryPublishTagMentionedIsRegistered(): void
    {
        $tags = $this->extract('/--tag=([\w-]+)/', 1);

        $this->assertNotEmpty($tags, 'Extraction found no publish tags');

        foreach ($tags as $tag) {
            $this->assertNotEmpty(ServiceProvider::pathsToPublish(VatValidatorServiceProvider::class, $tag), "Guidelines mention publish tag {$tag}, which the service provider does not register");
        }
    }

    /**
     * @return list<string>
     */
    private function extract(string $pattern, int $group = 0): array
    {
        preg_match_all($pattern, $this->rendered, $found);

        return array_values(array_unique($found[$group]));
    }

    /**
     * Short name to FQCN: `use ... as ...` aliases from the snippets, every
     * class under src/ by its basename, and the allowed framework classes.
     *
     * @return array<string, class-string>
     */
    private function classAliases(): array
    {
        $aliases = self::FRAMEWORK_CLASSES;

        foreach ($this->packageClasses() as $class) {
            $aliases[class_basename($class)] = $class;
        }

        preg_match_all('/^use\s+([\w\\\\]+)(?:\s+as\s+(\w+))?;/m', $this->rendered, $uses, PREG_SET_ORDER);

        foreach ($uses as $use) {
            $aliases[$use[2] ?? class_basename($use[1])] = $use[1];
        }

        return $aliases;
    }

    /**
     * @return list<class-string>
     */
    private function packageClasses(): array
    {
        $classes = [];
        $src = realpath(self::PACKAGE_ROOT . '/src');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            $relative = substr($file->getPathname(), strlen($src) + 1);
            $class = self::PACKAGE_NAMESPACE . '\\' . str_replace(['/', '.php'], ['\\', ''], $relative);

            if (class_exists($class) || interface_exists($class)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    /**
     * A facade forwards static calls to its root, so a method counts when
     * either the facade or the class behind it defines it.
     */
    private function hasMethod(string $class, string $method): bool
    {
        if (method_exists($class, $method)) {
            return true;
        }

        return is_subclass_of($class, Facade::class) && method_exists($class::getFacadeRoot(), $method);
    }
}
