<?php
namespace Formula\HairConcern\Api\Data;

interface HairConcernInterface
{
    const HAIRCONCERN_ID = 'hairconcern_id';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const LOGO = 'logo';
    const TAGS = 'tags';

    /**
     * @return int|null
     */
    public function getHairConcernId();

    /**
     * @param int $hairconcernId
     * @return $this
     */
    public function setHairConcernId($hairconcernId);

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