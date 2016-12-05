<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Villes
 *
 * @ORM\Table(name="villes", uniqueConstraints={@ORM\UniqueConstraint(name="uq_ville_insee_cp", columns={"ville", "insee", "cp"})}, indexes={@ORM\Index(name="idx_villes_cp", columns={"cp"}), @ORM\Index(name="idx_villes_ville_cp", columns={"ville", "cp"})})
 * @ORM\Entity
 */
class Villes
{
    /**
     * @var string
     *
     * @ORM\Column(name="ville", type="string", length=191, nullable=false)
     */
    private $ville;

    /**
     * @var string
     *
     * @ORM\Column(name="insee", type="string", length=16, nullable=true)
     */
    private $insee;

    /**
     * @var string
     *
     * @ORM\Column(name="cp", type="string", length=16, nullable=true)
     */
    private $cp;

    /**
     * @var string
     *
     * @ORM\Column(name="num_departement", type="string", length=16, nullable=true)
     */
    private $numDepartement;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active = '1';

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
     * @ORM\Column(name="id_ville", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idVille;



    /**
     * Set ville
     *
     * @param string $ville
     *
     * @return Villes
     */
    public function setVille($ville)
    {
        $this->ville = $ville;

        return $this;
    }

    /**
     * Get ville
     *
     * @return string
     */
    public function getVille()
    {
        return $this->ville;
    }

    /**
     * Set insee
     *
     * @param string $insee
     *
     * @return Villes
     */
    public function setInsee($insee)
    {
        $this->insee = $insee;

        return $this;
    }

    /**
     * Get insee
     *
     * @return string
     */
    public function getInsee()
    {
        return $this->insee;
    }

    /**
     * Set cp
     *
     * @param string $cp
     *
     * @return Villes
     */
    public function setCp($cp)
    {
        $this->cp = $cp;

        return $this;
    }

    /**
     * Get cp
     *
     * @return string
     */
    public function getCp()
    {
        return $this->cp;
    }

    /**
     * Set numDepartement
     *
     * @param string $numDepartement
     *
     * @return Villes
     */
    public function setNumDepartement($numDepartement)
    {
        $this->numDepartement = $numDepartement;

        return $this;
    }

    /**
     * Get numDepartement
     *
     * @return string
     */
    public function getNumDepartement()
    {
        return $this->numDepartement;
    }

    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return Villes
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Villes
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
     * @return Villes
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
     * Get idVille
     *
     * @return integer
     */
    public function getIdVille()
    {
        return $this->idVille;
    }
}
