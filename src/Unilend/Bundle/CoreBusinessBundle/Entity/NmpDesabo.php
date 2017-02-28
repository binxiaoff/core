<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * NmpDesabo
 *
 * @ORM\Table(name="nmp_desabo", uniqueConstraints={@ORM\UniqueConstraint(name="email", columns={"email"})})
 * @ORM\Entity
 */
class NmpDesabo
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=false)
     */
    private $idClient;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=false)
     */
    private $email;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_textemail", type="integer", nullable=false)
     */
    private $idTextemail;

    /**
     * @var string
     *
     * @ORM\Column(name="raison", type="string", length=255, nullable=false)
     */
    private $raison;

    /**
     * @var string
     *
     * @ORM\Column(name="commentaire", type="text", length=65535, nullable=false)
     */
    private $commentaire;

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
     * @ORM\Column(name="id_desabo", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idDesabo;



    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return NmpDesabo
     */
    public function setIdClient($idClient)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return integer
     */
    public function getIdClient()
    {
        return $this->idClient;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return NmpDesabo
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
     * Set idTextemail
     *
     * @param integer $idTextemail
     *
     * @return NmpDesabo
     */
    public function setIdTextemail($idTextemail)
    {
        $this->idTextemail = $idTextemail;

        return $this;
    }

    /**
     * Get idTextemail
     *
     * @return integer
     */
    public function getIdTextemail()
    {
        return $this->idTextemail;
    }

    /**
     * Set raison
     *
     * @param string $raison
     *
     * @return NmpDesabo
     */
    public function setRaison($raison)
    {
        $this->raison = $raison;

        return $this;
    }

    /**
     * Get raison
     *
     * @return string
     */
    public function getRaison()
    {
        return $this->raison;
    }

    /**
     * Set commentaire
     *
     * @param string $commentaire
     *
     * @return NmpDesabo
     */
    public function setCommentaire($commentaire)
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    /**
     * Get commentaire
     *
     * @return string
     */
    public function getCommentaire()
    {
        return $this->commentaire;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return NmpDesabo
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
     * @return NmpDesabo
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
     * Get idDesabo
     *
     * @return integer
     */
    public function getIdDesabo()
    {
        return $this->idDesabo;
    }
}
