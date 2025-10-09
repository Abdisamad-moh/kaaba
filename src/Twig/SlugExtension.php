<?php 
namespace App\Twig;

use Symfony\Component\String\Slugger\SluggerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SlugExtension extends AbstractExtension
{
    private $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('slugify', [$this, 'slugify']),
        ];
    }

    public function slugify(string $title): string
    {
        return $this->slugger->slug($title)->lower();
    }
}
