<?php 
// src/Twig/AppExtension.php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('shorten', [$this, 'shorten']),
            new TwigFilter('capitalizeWords', [$this, 'capitalizeWords']),
        ];
    }

    public function shorten(string $text, int $length, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . $suffix;
    }

    public function capitalizeWords(string $text): string
    {
        // Use ucwords() to capitalize the first letter of each word
        return ucwords($text);
    }
}
