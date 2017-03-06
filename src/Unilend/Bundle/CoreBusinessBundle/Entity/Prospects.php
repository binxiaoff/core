<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Prospects
 *
 * @ORM\Table(name="prospects", indexes={@ORM\Index(name="idx_prospects_email", columns={"email"})})
 * @ORM\Entity
 */
class Prospects
{
    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="text", length=16777215, nullable=false)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="prenom", type="text", length=16777215, nullable=false)
     */
    private $prenom;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=191, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="id_langue", type="string", length=3, nullable=false)
     */
    private $idLangue;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=191, nullable=false)
     */
    private $source;

    /**
     * @var string
     *
     * @ORM\Column(name="source2", type="string", length=191, nullable=false)
     */
    private $source2;

    /**
     * @var string
     *
     * @ORM\Column(name="source3", type="string", length=191, nullable=false)
     */
    private $source3;

    /**
     * @var string
     *
     * @ORM\Column(name="slug_origine", type="string", length=191, nullable=false)
     */
    private $slugOrigine;

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
     * @ORM\Column(name="id_prospect", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idProspect;



    /**
     * Set nom
     *
     * @param string $nom
     *
     * @return Prospects
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set prenom
     *
     * @param string $prenom
     *
     * @return Prospects
     */
    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * Get prenom
     *
     * @return string
     */
    public function getPrenom()
    {
        return $this->prenom;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Prospects
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set idLangue
     *
     * @param string $idLangue
     *
     * @return Prospects
     */
    public function setIdLangue($idLangue)
    {
        $this->idLangue = $idLangue;

        return $this;
    }

    /**
     * Get idLangue
     *
     * @return string
     */
    public function getIdLangue()
    {
        return $this->idLangue;
    }

    /**
     * Set source
     *
     * @param string $source
     *
     * @return Prospects
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set source2
     *
     * @param string $source2
     *
     * @return Prospects
     */
    public function setSource2($source2)
    {
        $this->source2 = $source2;

        return $this;
    }

    /**
     * Get source2
     *
     * @return string
     */
    public function getSource2()
    {
        return $this->source2;
    }

    /**
     * Set source3
     *
     * @param string $source3
     *
     * @return Prospects
     */
    public function setSource3($source3)
    {
        $this->source3 = $source3;

        return $this;
    }

    /**
     * Get source3
     *
     * @return string
     */
    public function getSource3()
    {
        return $this->source3;
    }

    /**
     * Set slugOrigine
     *
     * @param string $slugOrigine
     *
     * @return Prospects
     */
    public function setSlugOrigine($slugOrigine)
    {
        $this->slugOrigine = $slugOrigine;

        return $this;
    }

    /**
     * Get slugOrigine
     *
     * @return string
     */
    public function getSlugOrigine()
    {
        return $this->slugOrigine;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Prospects
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
     * @return Prospects
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
     * Get idProspect
     *
     * @return integer
     */
    public function getIdProspect()
    {
        return $this->idProspect;
    }
}
