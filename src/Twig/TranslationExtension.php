<?php
// src/Twig/TranslationExtension.php

namespace App\Twig;

use App\Service\TranslationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TranslationExtension extends AbstractExtension
{
    private TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('trans', [$this, 'translate']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_translations', [$this, 'getTranslations']),
        ];
    }

    public function translate(string $key): string
    {
        return $this->translationService->trans($key);
    }

    public function getTranslations(): array
    {
        return $this->translationService->getAllTranslations();
    }
}