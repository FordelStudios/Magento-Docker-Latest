<?php
declare(strict_types=1);

namespace Formula\SearchRelevance\Model;

use Formula\SearchRelevance\Api\SearchInterface;
use Formula\SearchRelevance\Api\Data\SearchResultInterface;
use Magento\Elasticsearch\SearchAdapter\ConnectionManager;
use Magento\Elasticsearch\Model\Config;
use Psr\Log\LoggerInterface;

/**
 * Custom search service that queries OpenSearch directly with proper relevance.
 *
 * Bypasses Magento's broken search adapter which can't handle modified query structures.
 * Builds a proper multi_match + match_phrase query with minimum_should_match.
 */
class Search implements SearchInterface
{
    private ConnectionManager $connectionManager;
    private Config $config;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionManager $connectionManager,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->connectionManager = $connectionManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function search(string $query, int $page = 1, int $pageSize = 20): SearchResultInterface
    {
        $query = trim($query);
        if (empty($query)) {
            return new SearchResult([], 0);
        }

        try {
            $indexPrefix = $this->config->getIndexPrefix();
            $index = $indexPrefix . '_product_1'; // Magento alias

            $esQuery = $this->buildQuery($query, $page, $pageSize);
            $esQuery['index'] = $index;

            $client = $this->connectionManager->getConnection();
            $response = $client->query($esQuery);

            return $this->processResponse($response);
        } catch (\Exception $e) {
            $this->logger->error('[Formula_SearchRelevance] Search error: ' . $e->getMessage());
            return new SearchResult([], 0);
        }
    }

    private function buildQuery(string $searchTerm, int $page, int $pageSize): array
    {
        $from = ($page - 1) * $pageSize;
        $words = preg_split('/\s+/', $searchTerm);
        $wordCount = count($words);

        // For single-word queries, use a simple multi_match without minimum_should_match
        $minimumShouldMatch = $wordCount >= 2 ? '75%' : '100%';

        // Override from Magento config if set
        $configMsm = $this->config->getElasticsearchConfigData('minimum_should_match');
        if (!empty($configMsm) && $wordCount >= 2) {
            $minimumShouldMatch = $configMsm;
        }

        $boolQuery = [
            'must' => [
                [
                    'multi_match' => [
                        'query' => $searchTerm,
                        'fields' => [
                            'name^10',
                            'short_description^3',
                        ],
                        'type' => 'best_fields',
                        'minimum_should_match' => $minimumShouldMatch,
                    ],
                ],
            ],
            'filter' => [
                ['term' => ['visibility' => 4]],
                ['term' => ['status' => 1]],
            ],
        ];

        // Add phrase boosting for multi-word queries
        if ($wordCount >= 2) {
            $boolQuery['should'] = [
                [
                    'match_phrase' => [
                        'name' => [
                            'query' => $searchTerm,
                            'boost' => 15,
                            'slop' => 2,
                        ],
                    ],
                ],
                [
                    'match_phrase' => [
                        'short_description' => [
                            'query' => $searchTerm,
                            'boost' => 5,
                            'slop' => 3,
                        ],
                    ],
                ],
            ];
        }

        return [
            'body' => [
                'query' => ['bool' => $boolQuery],
                'from' => $from,
                'size' => $pageSize,
                '_source' => false,
                'track_total_hits' => true,
            ],
        ];
    }

    private function processResponse(array $response): SearchResultInterface
    {
        $totalCount = $response['hits']['total']['value'] ?? 0;
        $items = [];

        foreach ($response['hits']['hits'] ?? [] as $hit) {
            $id = (int) $hit['_id'];
            $score = (float) ($hit['_score'] ?? 0);
            $items[] = new SearchItem($id, $score);
        }

        return new SearchResult($items, $totalCount);
    }
}
