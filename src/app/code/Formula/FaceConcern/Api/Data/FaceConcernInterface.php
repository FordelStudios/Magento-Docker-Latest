<?php
namespace Formula\FaceConcern\Api\Data;

interface FaceConcernInterface
{
    const FACECONCERN_ID = 'faceconcern_id';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const LOGO = 'logo';
    const TAGS = 'tags';

    /**
     * @return int|null
     */
    public function getFaceConcernId();

    /**
     * @param int $faceconcernId
     * @return $this
     */
    public function setFaceConcernId($faceconcernId);

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