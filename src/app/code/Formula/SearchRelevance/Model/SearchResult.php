<?php
declare(strict_types=1);

namespace Formula\SearchRelevance\Model;

use Formula\SearchRelevance\Api\Data\SearchResultInterface;

class SearchResult implements SearchResultInterface
{
    /** @var \Formula\SearchRelevance\Api\Data\SearchItemInterface[] */
    private array $items;
    private int $totalCount;

    /**
     * @param \Formula\SearchRelevance\Api\Data\SearchItemInterface[] $items
     * @param int $totalCount
     */
    public function __construct(array $items, int $totalCount)
    {
        $this->items = $items;
        $this->totalCount = $totalCount;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }
}
