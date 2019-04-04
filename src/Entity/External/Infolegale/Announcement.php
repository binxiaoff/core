<?php

namespace Unilend\Entity\External\Infolegale;

use JMS\Serializer\Annotation as JMS;

class Announcement
{
    /**
     * @var string
     *
     * @JMS\SerializedName("adID")
     * @JMS\Type("string")
     */
    private $id;

    /**
     * @var string
     *
     * @JMS\SerializedName("source")
     * @JMS\Type("string")
     */
    private $source;

    /**
     * @var string
     *
     * @JMS\SerializedName("origine")
     * @JMS\Type("string")
     */
    private $origine;

    /**
     * @var string
     *
     * @JMS\SerializedName("categorie")
     * @JMS\Type("string")
     */
    private $category;

    /**
     * @var \DateTime
     *
     * @JMS\SerializedName("dateParution")
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    private $publishedDate;

    /**
     * @var string
     *
     * @JMS\SerializedName("role")
     * @JMS\Type("string")
     */
    private $role;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getOrigine()
    {
        return $this->origine;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return \DateTime
     */
    public function getPublishedDate()
    {
        return $this->publishedDate;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }
}
