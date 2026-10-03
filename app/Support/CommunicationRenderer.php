<?php

// app/Support/CommunicationRenderer.php
namespace App\Support;
class CommunicationRenderer
{
    public static function render(string $template, array $placeholders): string
    {
        return preg_replace_callback(
            '/\{\{\s*(\w+)\s*\}\}/',
            fn ($m) => $placeholders[$m[1]] ?? $m[0],
            $template
        );
    }
}
