<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AcceptationsLegalDocs
 *
 * @ORM\Table(name="acceptations_legal_docs", indexes={@ORM\Index(name="id_client", columns={"id_client"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\AcceptationLegalDocsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AcceptationsLegalDocs
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_legal_doc", type="integer")
     */
    private $idLegalDoc;

    /**
     * @var string
     *
     * @ORM\Column(name="pdf_name", type="string", length=191, nullable=true)
     */
    private $pdfName;

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
     * @var \Unilend\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClient;

    /**
     * @var int
     *
     * @ORM\Column(name="id_acceptation", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idAcceptation;

    /**
     * @param int $idLegalDoc
     *
     * @return AcceptationsLegalDocs
     */
    public function setIdLegalDoc(int $idLegalDoc): AcceptationsLegalDocs
    {
        $this->idLegalDoc = $idLegalDoc;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdLegalDoc(): int
    {
        return $this->idLegalDoc;
    }

    /**
     * @param Clients $idClient
     *
     * @return AcceptationsLegalDocs
     */
    public function setIdClient(Clients $idClient): AcceptationsLegalDocs
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * @return Clients
     */
    public function getIdClient(): Clients
    {
        return $this->idClient;
    }

    /**
     * @param \DateTime $added
     *
     * @return AcceptationsLegalDocs
     */
    public function setAdded(\DateTime $added): AcceptationsLegalDocs
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * @param \DateTime|null $updated
     *
     * @return AcceptationsLegalDocs
     */
    public function setUpdated(?\DateTime $updated): AcceptationsLegalDocs
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * @return integer
     */
    public function getIdAcceptation(): int
    {
        return $this->idAcceptation;
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

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }

    /**
     * @param string|null $pdfName
     *
     * @return AcceptationsLegalDocs
     */
    public function setPdfName(?string $pdfName): AcceptationsLegalDocs
    {
        $this->pdfName = $pdfName;

        return $this;

    }

    /**
     * @return string|null
     */
    public function getPdfName(): ?string
    {
        return $this->pdfName;
    }
}
