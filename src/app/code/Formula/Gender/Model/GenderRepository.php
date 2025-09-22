<?php

namespace Formula\Gender\Model;

use Formula\Gender\Api\GenderRepositoryInterface;
use Formula\Gender\Model\Product\Attribute\Source\Gender;

class GenderRepository implements GenderRepositoryInterface
{
    /**
     * @var Gender
     */
    private $genderSource;

    /**
     * Constructor
     *
     * @param Gender $genderSource
     */
    public function __construct(Gender $genderSource)
    {
        $this->genderSource = $genderSource;
    }

    /**
     * Get all gender options
     *
     * @return array
     */
    public function getGenderOptions()
    {
        return $this->genderSource->getAllOptions();
    }
}