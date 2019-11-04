<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Interfaces\FileStorageInterface;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Table(name="acceptations_legal_docs")
 * @ORM\Entity(repositoryClass="Unilend\Repository\AcceptationLegalDocsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AcceptationsLegalDocs implements FileStorageInterface
{
    use TimestampableTrait;

    /**
     * @var LegalDocument
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\LegalDocument")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_legal_doc", nullable=false)
     * })
     */
    private $legalDoc;

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
     * @var string
     *
     * @ORM\Column(type="string", length=191, nullable=true)
     */
    private $relativeFilePath;

    /**
     * AcceptationsLegalDocs constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->added = new DateTimeImmutable();
    }

    /**
     * @param LegalDocument $legalDoc
     *
     * @return AcceptationsLegalDocs
     */
    public function setLegalDoc(LegalDocument $legalDoc): AcceptationsLegalDocs
    {
        $this->legalDoc = $legalDoc;

        return $this;
    }

    /**
     * @return LegalDocument
     */
    public function getLegalDoc(): LegalDocument
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
     * @param string|null $relativeFilePath
     *
     * @return self
     */
    public function setRelativeFilePath(?string $relativeFilePath): AcceptationsLegalDocs
    {
        $this->relativeFilePath = $relativeFilePath;

        return $this;
    }

    /**
     * @return string
     */
    public function getRelativeFilePath(): ?string
    {
        return $this->relativeFilePath;
    }
}
