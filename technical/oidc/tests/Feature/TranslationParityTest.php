<?php

namespace Technical\Oidc\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TranslationParityTest extends TestCase
{
    private const LANG = __DIR__.'/../../lang';

    #[Test]
    #[DataProvider('translationFiles')]
    public function french_and_english_carry_the_same_keys(string $file): void
    {
        $french = $this->keys(require self::LANG."/fr/{$file}");
        $english = $this->keys(require self::LANG."/en/{$file}");

        $this->assertSame(
            [],
            array_values(array_diff($french, $english)),
            "lang/en/{$file} is missing keys that lang/fr/{$file} carries.",
        );

        $this->assertSame(
            [],
            array_values(array_diff($english, $french)),
            "lang/fr/{$file} is missing keys that lang/en/{$file} carries.",
        );
    }

    #[Test]
    public function neither_language_holds_a_file_the_other_does_not(): void
    {
        $this->assertSame(
            $this->files(self::LANG.'/fr'),
            $this->files(self::LANG.'/en'),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function translationFiles(): array
    {
        $cases = [];

        foreach (glob(self::LANG.'/fr/*.php') as $path) {
            $cases[basename($path)] = [basename($path)];
        }

        return $cases;
    }

    /**
     * @param  array<string, mixed>  $messages
     * @return list<string>
     */
    private function keys(array $messages, string $prefix = ''): array
    {
        $keys = [];

        foreach ($messages as $key => $message) {
            $keys = array_merge(
                $keys,
                is_array($message) ? $this->keys($message, "{$prefix}{$key}.") : ["{$prefix}{$key}"],
            );
        }

        sort($keys);

        return $keys;
    }

    /**
     * @return list<string>
     */
    private function files(string $directory): array
    {
        $files = array_map('basename', glob($directory.'/*.php'));

        $this->assertNotEmpty($files, "No translation file under {$directory}.");
        sort($files);

        return $files;
    }
}
