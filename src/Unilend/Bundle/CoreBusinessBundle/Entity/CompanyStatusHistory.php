<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyStatusHistory
 *
 * @ORM\Table(name="company_status_history", indexes={@ORM\Index(name="fk_company_status_history_id_company", columns={"id_company"}), @ORM\Index(name="idx_company_status_history_id_status", columns={"id_status"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class CompanyStatusHistory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Companies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company", referencedColumnName="id_company")
     * })
     */
    private $idCompany;

    /**
     * @var CompanyStatus
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_status", referencedColumnName="id")
     * })
     */
    private $idStatus;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user", referencedColumnName="id_user")
     * })
     */
    private $idUser;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", length=16777215, nullable=true)
     */
    private $content;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @return Companies
     */
    public function getIdCompany()
    {
        return $this->idCompany;
    }

    /**
     * @param Companies $idCompany
     *
     * @return CompanyStatusHistory
     */
    public function setIdCompany(Companies $idCompany)
    {
        $this->idCompany = $idCompany;

        return $this;
    }

    /**
     * @return CompanyStatus
     */
    public function getIdStatus()
    {
        return $this->idStatus;
    }

    /**
     * @param CompanyStatus $idStatus
     *
     * @return CompanyStatusHistory
     */
    public function setIdStatus(CompanyStatus $idStatus)
    {
        $this->idStatus = $idStatus;

        return $this;
    }

    /**
     * @return Users
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * @param Users $idUser
     *
     * @return CompanyStatusHistory
     */
    public function setIdUser(Users $idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return CompanyStatusHistory
     */
    public function setContent($content)
    {
        $this->content = $content;

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
     * @param \DateTime $added
     *
     * @return CompanyStatusHistory
     */
    public function setAdded(\DateTime $added)
    {
        $this->added = $added;

        return $this;
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
}
