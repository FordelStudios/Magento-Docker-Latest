<?php
declare(strict_types=1);

namespace Formula\SearchRelevance\Api;

/**
 * Custom search API that queries OpenSearch directly with proper relevance.
 */
interface SearchInterface
{
    /**
     * Search products with proper relevance scoring.
     *
     * @param string $query
     * @param int $page
     * @param int $pageSize
     * @return \Formula\SearchRelevance\Api\Data\SearchResultInterface
     */
    public function search(string $query, int $page = 1, int $pageSize = 20): Data\SearchResultInterface;
}
