<?php
declare(strict_types=1);

namespace Formula\SearchRelevance\Api\Data;

/**
 * Search result containing product IDs and total count.
 */
interface SearchResultInterface
{
    /**
     * @return \Formula\SearchRelevance\Api\Data\SearchItemInterface[]
     */
    public function getItems(): array;

    /**
     * @return int
     */
    public function getTotalCount(): int;
}
