<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Tests\Support;

/** Test-Double: Identity-Lokalisierung (Quelltexte) für Unit-Tests. */
final class FakeL10N implements \OCP\IL10N {
    public function t(string $text, $parameters = []): string {
        foreach ((array)$parameters as $name => $value) {
            $text = str_replace('{' . $name . '}', (string)$value, $text);
        }
        return $text;
    }

    public function n(string $text_singular, string $text_plural, int $count, array $parameters = []): string {
        return $this->t($count === 1 ? $text_singular : $text_plural, $parameters);
    }

    public function l(string $type, $data, array $options = []) {
        return $data;
    }

    public function getLanguageCode(): string {
        return 'de';
    }

    public function getLocaleCode(): string {
        return 'de_DE';
    }
}
