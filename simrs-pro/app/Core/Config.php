<?php
declare(strict_types=1);

namespace App\Core;

class Config
{
    private static array $items = [];

    public static function load(string $file): void
    {
        if (!file_exists($file)) return;
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            if (strpos($line, '=') === false) continue;
            [$key, $value] = explode('=', $line, 2);
            self::$items[trim($key)] = trim($value, ""' ");
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$items[$key] ?? $default;
    }

    public static function isDebug(): bool
    {
        return self::get('APP_DEBUG') === 'true';
    }
}
