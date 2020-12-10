<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource};
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Clients;
use Unilend\Core\Entity\LegalDocument;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableAddedOnlyTrait};
use Unilend\Core\Filter\CountFilter;

/**
 * @ApiResource(
 *     attributes={
 *         "route_prefix"="/core"
 *     },
 *     normalizationContext={"groups": {"acceptationsLegalDocs:read", "timestampable:read"}},
 *     denormalizationContext={"groups": {"acceptationsLegalDocs:write"}},
 *     collectionOperations={
 *         "post": {
 *            "security_post_denormalize": "is_granted('create', object)"
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *     }
 * )
 *
 * @ApiFilter(CountFilter::class)
 *
 * @ORM\Table(
 *     uniqueConstraints={
 *      @ORM\UniqueConstraint(columns={"id_legal_doc", "accepted_by"})
 *     },
 *     name="core_acceptations_legal_docs"
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity(fields={"legalDoc", "acceptedBy"}, message="AcceptationsLegalDocs.legalDoc.unique")
 */
class AcceptationsLegalDocs
{
    use TimestampableAddedOnlyTrait;
    use PublicizeIdentityTrait;

    /**
     * @var LegalDocument
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\LegalDocument")
     * @ORM\JoinColumn(name="id_legal_doc", nullable=false)
     *
     * @Assert\Expression(
     *     "this.getLegalDoc().getId() === constant('Unilend\\Core\\Entity\\LegalDocument::CURRENT_SERVICE_TERMS')",
     *     message="AcceptationsLegalDocs.legalDoc.notCurrent"
     * )
     *
     * @Groups({"acceptationsLegalDocs:read", "acceptationsLegalDocs:write"})
     */
    private LegalDocument $legalDoc;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Clients")
     * @ORM\JoinColumn(name="accepted_by", nullable=false)
     *
     */
    private Clients $acceptedBy;

    /**
     * @param Clients       $acceptedBy
     * @param LegalDocument $legalDoc
     *
     * @throws \Exception
     */
    public function __construct(Clients $acceptedBy, LegalDocument $legalDoc)
    {
        $this->legalDoc   = $legalDoc;
        $this->acceptedBy = $acceptedBy;
        $this->added      = new DateTimeImmutable();
    }

    /**
     * @return LegalDocument
     */
    public function getLegalDoc(): LegalDocument
    {
        return $this->legalDoc;
    }

    /**
     * @return Clients
     */
    public function getAcceptedBy(): Clients
    {
        return $this->acceptedBy;
    }
}
