<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PartenairesSubcodes
 *
 * @ORM\Table(name="partenaires_subcodes")
 * @ORM\Entity
 */
class PartenairesSubcodes
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_partenaire", type="integer", nullable=false)
     */
    private $idPartenaire;

    /**
     * @var string
     *
     * @ORM\Column(name="partenaire_subcode", type="string", length=191, nullable=false)
     */
    private $partenaireSubcode;

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
     * @ORM\Column(name="id_partenaire_subcode", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idPartenaireSubcode;



    /**
     * Set idPartenaire
     *
     * @param integer $idPartenaire
     *
     * @return PartenairesSubcodes
     */
    public function setIdPartenaire($idPartenaire)
    {
        $this->idPartenaire = $idPartenaire;

        return $this;
    }

    /**
     * Get idPartenaire
     *
     * @return integer
     */
    public function getIdPartenaire()
    {
        return $this->idPartenaire;
    }

    /**
     * Set partenaireSubcode
     *
     * @param string $partenaireSubcode
     *
     * @return PartenairesSubcodes
     */
    public function setPartenaireSubcode($partenaireSubcode)
    {
        $this->partenaireSubcode = $partenaireSubcode;

        return $this;
    }

    /**
     * Get partenaireSubcode
     *
     * @return string
     */
    public function getPartenaireSubcode()
    {
        return $this->partenaireSubcode;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return PartenairesSubcodes
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
     * @return PartenairesSubcodes
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
     * Get idPartenaireSubcode
     *
     * @return integer
     */
    public function getIdPartenaireSubcode()
    {
        return $this->idPartenaireSubcode;
    }
}
