<?php
declare(strict_types=1);

namespace Formula\SearchRelevance\Plugin;

/**
 * This plugin is intentionally empty.
 * Search relevance is now handled by the custom REST endpoint (Formula\SearchRelevance\Model\Search).
 * The MapperPlugin approach was abandoned because Magento's response adapter breaks
 * when must clauses or min_score are injected into the ES query.
 */
class MapperPlugin
{
    // Intentionally empty - kept for reference
}
