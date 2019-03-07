<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class CloseOutNettingEmailExtraContent
 *
 * @ORM\Table(name="close_out_netting_email_extra_content")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class CloseOutNettingEmailExtraContent
{
    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
     */
    private $idProject;

    /**
     * @var string
     *
     * @ORM\Column(name="lenders_content", type="text", length=16777215, nullable=true)
     */
    private $lendersContent;

    /**
     * @var string
     *
     * @ORM\Column(name="borrower_content", type="text", length=16777215, nullable=true)
     */
    private $borrowerContent;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @return Projects
     */
    public function getIdProject(): Projects
    {
        return $this->idProject;
    }

    /**
     * @param Projects $idProject
     *
     * @return CloseOutNettingEmailExtraContent
     */
    public function setIdProject(Projects $idProject): CloseOutNettingEmailExtraContent
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLendersContent(): ?string
    {
        return $this->lendersContent;
    }

    /**
     * @param string|null $lenderContent
     *
     * @return CloseOutNettingEmailExtraContent
     */
    public function setLendersContent(?string $lenderContent): CloseOutNettingEmailExtraContent
    {
        $this->lendersContent = $lenderContent;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBorrowerContent(): ?string
    {
        return $this->borrowerContent;
    }

    /**
     * @param string|null $borrowerContent
     *
     * @return CloseOutNettingEmailExtraContent
     */
    public function setBorrowerContent(?string $borrowerContent): CloseOutNettingEmailExtraContent
    {
        $this->borrowerContent = $borrowerContent;

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->added->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }
}
