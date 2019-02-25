<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Translations
 *
 * @ORM\Table(name="translations", uniqueConstraints={@ORM\UniqueConstraint(name="unq_translation", columns={"locale", "section", "name"})}, indexes={@ORM\Index(name="section", columns={"section"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\TranslationsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Translations
{
    const SECTION_MAIL_TITLE = 'mail-title';

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=5)
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="section", type="string", length=191)
     */
    private $section;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="translation", type="text", length=65535)
     */
    private $translation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_translation", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idTranslation;

    /**
     * @param string $locale
     *
     * @return Translations
     */
    public function setLocale(string $locale): Translations
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $section
     *
     * @return Translations
     */
    public function setSection(string $section): Translations
    {
        $this->section = $section;

        return $this;
    }

    /**
     * @return string
     */
    public function getSection(): string
    {
        return $this->section;
    }

    /**
     * @param string $name
     *
     * @return Translations
     */
    public function setName(string $name): Translations
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $translation
     *
     * @return Translations
     */
    public function setTranslation(string $translation): Translations
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * @return string
     */
    public function getTranslation(): string
    {
        return $this->translation;
    }

    /**
     * @param \DateTime $added
     *
     * @return Translations
     */
    public function setAdded(\DateTime $added): Translations
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * @param \DateTime|null $updated
     *
     * @return Translations
     */
    public function setUpdated(?\DateTime $updated): Translations
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * @return int
     */
    public function getIdTranslation(): int
    {
        return $this->idTranslation;
    }
    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function seUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
