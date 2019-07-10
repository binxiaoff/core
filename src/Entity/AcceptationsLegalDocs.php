<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Table(name="acceptations_legal_docs")
 * @ORM\Entity(repositoryClass="Unilend\Repository\AcceptationLegalDocsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AcceptationsLegalDocs
{
    use TimestampableTrait;

    /**
     * @var Tree
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Tree")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_legal_doc", referencedColumnName="id_tree", nullable=false)
     * })
     */
    private $legalDoc;

    /**
     * @var string
     *
     * @ORM\Column(name="pdf_name", type="string", length=191, nullable=true)
     */
    private $pdfName;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $client;

    /**
     * @var int
     *
     * @ORM\Column(name="id_acceptation", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idAcceptation;

    /**
     * @param Tree $legalDoc
     *
     * @return AcceptationsLegalDocs
     */
    public function setLegalDoc(Tree $legalDoc): AcceptationsLegalDocs
    {
        $this->legalDoc = $legalDoc;

        return $this;
    }

    /**
     * @return Tree
     */
    public function getLegalDoc(): Tree
    {
        return $this->legalDoc;
    }

    /**
     * @param Clients $client
     *
     * @return AcceptationsLegalDocs
     */
    public function setClient(Clients $client): AcceptationsLegalDocs
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Clients
     */
    public function getClient(): Clients
    {
        return $this->client;
    }

    /**
     * @return int
     */
    public function getIdAcceptation(): int
    {
        return $this->idAcceptation;
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
