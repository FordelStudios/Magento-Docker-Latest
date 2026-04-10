<?php
declare(strict_types=1);

namespace Formula\SearchRelevance\Api\Data;

/**
 * A single search result item with product ID and relevance score.
 */
interface SearchItemInterface
{
    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @return float
     */
    public function getScore(): float;
}
