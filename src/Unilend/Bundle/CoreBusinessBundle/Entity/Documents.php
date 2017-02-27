<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Documents
 *
 * @ORM\Table(name="documents")
 * @ORM\Entity
 */
class Documents
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_company", type="integer", nullable=false)
     */
    private $idCompany;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_lender", type="integer", nullable=false)
     */
    private $idLender;

    /**
     * @var string
     *
     * @ORM\Column(name="id_loan", type="string", length=45, nullable=false)
     */
    private $idLoan;

    /**
     * @var string
     *
     * @ORM\Column(name="id_loanrequest", type="string", length=45, nullable=false)
     */
    private $idLoanrequest;

    /**
     * @var string
     *
     * @ORM\Column(name="objet", type="string", length=45, nullable=false)
     */
    private $objet;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="text", length=16777215, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=45, nullable=false)
     */
    private $path;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="visibility", type="string", length=45, nullable=false)
     */
    private $visibility;

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
     * @var string
     *
     * @ORM\Column(name="validated", type="string", length=45, nullable=false)
     */
    private $validated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_document", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idDocument;



    /**
     * Set idCompany
     *
     * @param integer $idCompany
     *
     * @return Documents
     */
    public function setIdCompany($idCompany)
    {
        $this->idCompany = $idCompany;

        return $this;
    }

    /**
     * Get idCompany
     *
     * @return integer
     */
    public function getIdCompany()
    {
        return $this->idCompany;
    }

    /**
     * Set idUser
     *
     * @param integer $idUser
     *
     * @return Documents
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return integer
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * Set idLender
     *
     * @param integer $idLender
     *
     * @return Documents
     */
    public function setIdLender($idLender)
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * Get idLender
     *
     * @return integer
     */
    public function getIdLender()
    {
        return $this->idLender;
    }

    /**
     * Set idLoan
     *
     * @param string $idLoan
     *
     * @return Documents
     */
    public function setIdLoan($idLoan)
    {
        $this->idLoan = $idLoan;

        return $this;
    }

    /**
     * Get idLoan
     *
     * @return string
     */
    public function getIdLoan()
    {
        return $this->idLoan;
    }

    /**
     * Set idLoanrequest
     *
     * @param string $idLoanrequest
     *
     * @return Documents
     */
    public function setIdLoanrequest($idLoanrequest)
    {
        $this->idLoanrequest = $idLoanrequest;

        return $this;
    }

    /**
     * Get idLoanrequest
     *
     * @return string
     */
    public function getIdLoanrequest()
    {
        return $this->idLoanrequest;
    }

    /**
     * Set objet
     *
     * @param string $objet
     *
     * @return Documents
     */
    public function setObjet($objet)
    {
        $this->objet = $objet;

        return $this;
    }

    /**
     * Get objet
     *
     * @return string
     */
    public function getObjet()
    {
        return $this->objet;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Documents
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return Documents
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return Documents
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Documents
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set visibility
     *
     * @param string $visibility
     *
     * @return Documents
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Documents
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
     * @return Documents
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
     * Set validated
     *
     * @param string $validated
     *
     * @return Documents
     */
    public function setValidated($validated)
    {
        $this->validated = $validated;

        return $this;
    }

    /**
     * Get validated
     *
     * @return string
     */
    public function getValidated()
    {
        return $this->validated;
    }

    /**
     * Get idDocument
     *
     * @return integer
     */
    public function getIdDocument()
    {
        return $this->idDocument;
    }
}
