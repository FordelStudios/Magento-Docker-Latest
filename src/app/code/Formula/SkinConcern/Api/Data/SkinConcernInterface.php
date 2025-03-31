<?php
namespace Formula\SkinConcern\Api\Data;

interface SkinConcernInterface
{
    const SKINCONCERN_ID = 'skinconcern_id';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const LOGO = 'logo';
    const TAGS = 'tags';

    /**
     * @return int|null
     */
    public function getSkinConcernId();

    /**
     * @param int $skinconcernId
     * @return $this
     */
    public function setSkinConcernId($skinconcernId);

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * @return string|null
     */
    public function getDescription();

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * @return string|null
     */
    public function getLogo();

    /**
     * @param string $logo
     * @return $this
     */
    public function setLogo($logo);

    /**
     * @return string|null
     */
    public function getTags();

    /**
     * @param string|mixed[] $tags
     * @return $this
     */
    public function setTags($tags);

}