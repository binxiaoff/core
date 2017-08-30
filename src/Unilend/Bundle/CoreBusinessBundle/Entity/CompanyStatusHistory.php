<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyStatusHistory
 *
 * @ORM\Table(name="company_status_history", indexes={@ORM\Index(name="fk_company_status_history_id_company", columns={"id_company"}), @ORM\Index(name="idx_company_status_history_id_status", columns={"id_status"}), @ORM\Index(name="idx_company_status_history_changed_on", columns={"changed_on"})})
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
     * @var \DateTime
     *
     * @ORM\Column(name="changed_on", type="date", nullable=true)
     */
    private $changedOn;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver", type="text", length=16777215, nullable=true)
     */
    private $receiver;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_content", type="text", length=16777215, nullable=true)
     */
    private $mailContent;

    /**
     * @var string
     *
     * @ORM\Column(name="site_content", type="text", length=16777215, nullable=true)
     */
    private $siteContent;

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
     * @return \DateTime
     */
    public function getChangedOn()
    {
        return $this->changedOn;
    }

    /**
     * @param \DateTime $changedOn
     *
     * @return CompanyStatusHistory
     */
    public function setChangedOn(\DateTime $changedOn)
    {
        $this->changedOn = $changedOn;

        return $this;
    }

    /**
     * @return string
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * @param string $receiver
     *
     * @return CompanyStatusHistory
     */
    public function setReceiver($receiver)
    {
        $this->receiver = $receiver;

        return $this;
    }

    /**
     * @return string
     */
    public function getMailContent()
    {
        return $this->mailContent;
    }

    /**
     * @param string $mailContent
     *
     * @return CompanyStatusHistory
     */
    public function setMailContent($mailContent)
    {
        $this->mailContent = $mailContent;

        return $this;
    }

    /**
     * @return string
     */
    public function getSiteContent()
    {
        return $this->siteContent;
    }

    /**
     * @param string $siteContent
     *
     * @return CompanyStatusHistory
     */
    public function setSiteContent($siteContent)
    {
        $this->siteContent = $siteContent;

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
