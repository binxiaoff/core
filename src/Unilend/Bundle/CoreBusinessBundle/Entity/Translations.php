<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Translations
 *
 * @ORM\Table(name="translations", indexes={@ORM\Index(name="section", columns={"section"})})
 * @ORM\Entity
 */
class Translations
{
    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=5, nullable=true)
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="section", type="string", length=191, nullable=false)
     */
    private $section;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="translation", type="text", length=65535, nullable=true)
     */
    private $translation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_translation", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idTranslation;



    /**
     * Set locale
     *
     * @param string $locale
     *
     * @return Translations
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set section
     *
     * @param string $section
     *
     * @return Translations
     */
    public function setSection($section)
    {
        $this->section = $section;

        return $this;
    }

    /**
     * Get section
     *
     * @return string
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Translations
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set translation
     *
     * @param string $translation
     *
     * @return Translations
     */
    public function setTranslation($translation)
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * Get translation
     *
     * @return string
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Translations
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return Translations
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get idTranslation
     *
     * @return integer
     */
    public function getIdTranslation()
    {
        return $this->idTranslation;
    }
}
