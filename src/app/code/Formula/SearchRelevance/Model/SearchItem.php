<?php
declare(strict_types=1);

namespace Formula\SearchRelevance\Model;

use Formula\SearchRelevance\Api\Data\SearchItemInterface;

class SearchItem implements SearchItemInterface
{
    private int $id;
    private float $score;

    public function __construct(int $id, float $score)
    {
        $this->id = $id;
        $this->score = $score;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getScore(): float
    {
        return $this->score;
    }
}
