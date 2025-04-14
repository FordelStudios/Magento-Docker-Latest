<?php
namespace Formula\Review\Api;

interface ReviewImageUploadInterface
{
    /**
     * Upload review image
     *
     * @return string[] Image upload result
     */
    public function upload();
}