<?php
declare(strict_types=1);

namespace Formula\SearchRelevance\Plugin;

use Magento\Elasticsearch\Model\Adapter\Index\Builder;

/**
 * Adds a search-time analyzer with synonyms for the default index.
 *
 * OpenSearch/ES can't use keyword_repeat + synonym_graph in the same analyzer.
 * So instead of modifying the "default" analyzer (used at index time), we create
 * a "default_search" analyzer that OpenSearch automatically uses at search time
 * for fields analyzed with "default".
 *
 * This means:
 * - Index time: uses "default" analyzer (no synonyms, keeps keyword_repeat + stemmer)
 * - Search time: uses "default_search" analyzer (with synonyms, no keyword_repeat)
 *
 * OpenSearch convention: if an analyzer named "{name}_search" exists, it's used
 * automatically at search time for fields using "{name}" analyzer.
 * @see https://opensearch.org/docs/latest/analyzers/search-analyzers/
 */
class IndexBuilderPlugin
{
    /**
     * After the Index Builder produces analysis settings, add a search-time analyzer with synonyms.
     *
     * @param Builder $subject
     * @param array $result
     * @return array
     */
    public function afterBuild(Builder $subject, array $result): array
    {
        // Only act if synonyms are configured
        if (!isset($result['analysis']['filter']['synonyms'])) {
            return $result;
        }

        // Create "default_search" analyzer: same as "default" but with synonyms
        // and without keyword_repeat (which conflicts with synonym_graph)
        $result['analysis']['analyzer']['default_search'] = [
            'type' => 'custom',
            'tokenizer' => $result['analysis']['analyzer']['default']['tokenizer'] ?? 'default_tokenizer',
            'filter' => [
                'lowercase',
                'asciifolding',
                'synonyms',
                'default_stemmer',
                'unique_stem',
            ],
            'char_filter' => $result['analysis']['analyzer']['default']['char_filter'] ?? ['default_char_filter'],
        ];

        return $result;
    }
}
