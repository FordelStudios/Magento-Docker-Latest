<?php
namespace Formula\BodyConcern\Api\Data;

interface BodyConcernInterface
{
    const BODYCONCERN_ID = 'bodyconcern_id';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const LOGO = 'logo';
    const TAGS = 'tags';

    /**
     * @return int|null
     */
    public function getBodyConcernId();

    /**
     * @param int $bodyconcernId
     * @return $this
     */
    public function setBodyConcernId($bodyconcernId);

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