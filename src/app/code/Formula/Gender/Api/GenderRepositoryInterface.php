<?php

namespace Formula\Gender\Api;

interface GenderRepositoryInterface
{
    /**
     * Get all gender options
     *
     * @return array
     */
    public function getGenderOptions();
}