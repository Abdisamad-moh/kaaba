<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

 namespace App\Twig;

use App\Repository\MetierPackagesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Doctrine\ORM\Tools\Pagination\Paginator;

#[AsLiveComponent('Resumes')]
class Resumes
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public int $page = 1;

    #[LiveProp]
    public array $packages_list = []; // Retain fetched packages

    #[LiveProp(writable: true, url: true)]
    public ?string $query = null;

    #[ExposeInTemplate]
    private bool $has_more = true;

    private const PER_PAGE = 16;

    public function __construct(
        private readonly MetierPackagesRepository $packages_repo,
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    #[LiveAction]
    public function more(): void
    {
        ++$this->page;
    }

    public function getPackages(): array
    {
        $offset = ($this->page - 1) * self::PER_PAGE;

        // Fetch paginated results
        $newPackages = $this->packages_repo->paginatePackagesPerTypeAndQuery(
            offset: $offset,
            num_rows: self::PER_PAGE,
            single_scaler: false,
            type: 'resume',
            query: $this->query
        )->getQuery()->getResult();

        // Check if there are more packages
        $extraPackages = $this->packages_repo->paginatePackagesPerTypeAndQuery(
            offset: $offset + self::PER_PAGE,
            num_rows: 1, // Fetch one more item to check
            single_scaler: true,
            type: 'resume',
            query: $this->query
        )->getQuery()->getScalarResult();

        $this->has_more = count($extraPackages) > 0;

        // Merge new packages into the existing list
        $this->packages_list = array_merge($this->packages_list, $newPackages);

        return $this->packages_list;
    }

    #[ExposeInTemplate]
    public function hasMore(): bool
    {
        return $this->has_more;
    }

    // Add a setter for query to handle search reset
    public function setQuery(?string $query): void
    {
        if ($this->query !== $query) {
            $this->query = $query;

            // Reset pagination and packages list when query changes
            $this->page = 1;
            dd($query, $this->query);
            // $this->packages_list = [];

            // Trigger a refresh of the first page of results
            // $this->getPackages();
        }
    }
}
