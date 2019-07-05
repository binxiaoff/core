<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="settings")
 * @ORM\Entity
 */
class Settings
{
    public const TYPE_AUTOBID_GLOBAL_SWITCH = 'Auto-bid global switch';
    public const TYPE_AUTOBID_STEP          = 'Auto-bid step';

    public const TYPE_LENDER_TOS_LEGAL_ENTITY   = 'Lien conditions generales inscription preteur societe';
    public const TYPE_LENDER_TOS_NATURAL_PERSON = 'Lien conditions generales inscription preteur particulier';
    public const TYPE_DATE_LENDER_TOS           = 'Date nouvelles CGV avec 2 mandats';
    public const TYPE_BORROWER_TOS              = 'Lien conditions generales depot dossier';

    public const TYPE_TERMS_OF_SALE_PAGE_ID = 'TERMS_OF_SALE_PAGE_ID';

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=191, unique=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", length=16777215)
     */
    private $value;

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
     * @ORM\Column(name="id_setting", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idSetting;

    /**
     * @param string $type
     *
     * @return Settings
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $value
     *
     * @return Settings
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param \DateTime $added
     *
     * @return Settings
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @param \DateTime $updated
     *
     * @return Settings
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @return int
     */
    public function getIdSetting()
    {
        return $this->idSetting;
    }
}
