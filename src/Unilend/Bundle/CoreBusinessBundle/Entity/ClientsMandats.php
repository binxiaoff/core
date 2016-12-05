<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsMandats
 *
 * @ORM\Table(name="clients_mandats", indexes={@ORM\Index(name="id_client", columns={"id_client"})})
 * @ORM\Entity
 */
class ClientsMandats
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=false)
     */
    private $idClient;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project", type="integer", nullable=false)
     */
    private $idProject;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="id_universign", type="string", length=191, nullable=false)
     */
    private $idUniversign;

    /**
     * @var string
     *
     * @ORM\Column(name="url_universign", type="string", length=191, nullable=false)
     */
    private $urlUniversign;

    /**
     * @var string
     *
     * @ORM\Column(name="url_pdf", type="string", length=191, nullable=false)
     */
    private $urlPdf;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="iban", type="string", length=34, nullable=false)
     */
    private $iban;

    /**
     * @var string
     *
     * @ORM\Column(name="bic", type="string", length=11, nullable=false)
     */
    private $bic;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_mandat", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idMandat;



    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return ClientsMandats
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
     * Set idProject
     *
     * @param integer $idProject
     *
     * @return ClientsMandats
     */
    public function setIdProject($idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return integer
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ClientsMandats
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
     * Set idUniversign
     *
     * @param string $idUniversign
     *
     * @return ClientsMandats
     */
    public function setIdUniversign($idUniversign)
    {
        $this->idUniversign = $idUniversign;

        return $this;
    }

    /**
     * Get idUniversign
     *
     * @return string
     */
    public function getIdUniversign()
    {
        return $this->idUniversign;
    }

    /**
     * Set urlUniversign
     *
     * @param string $urlUniversign
     *
     * @return ClientsMandats
     */
    public function setUrlUniversign($urlUniversign)
    {
        $this->urlUniversign = $urlUniversign;

        return $this;
    }

    /**
     * Get urlUniversign
     *
     * @return string
     */
    public function getUrlUniversign()
    {
        return $this->urlUniversign;
    }

    /**
     * Set urlPdf
     *
     * @param string $urlPdf
     *
     * @return ClientsMandats
     */
    public function setUrlPdf($urlPdf)
    {
        $this->urlPdf = $urlPdf;

        return $this;
    }

    /**
     * Get urlPdf
     *
     * @return string
     */
    public function getUrlPdf()
    {
        return $this->urlPdf;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return ClientsMandats
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set iban
     *
     * @param string $iban
     *
     * @return ClientsMandats
     */
    public function setIban($iban)
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * Get iban
     *
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * Set bic
     *
     * @param string $bic
     *
     * @return ClientsMandats
     */
    public function setBic($bic)
    {
        $this->bic = $bic;

        return $this;
    }

    /**
     * Get bic
     *
     * @return string
     */
    public function getBic()
    {
        return $this->bic;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return ClientsMandats
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ClientsMandats
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
     * Get idMandat
     *
     * @return integer
     */
    public function getIdMandat()
    {
        return $this->idMandat;
    }
}
