<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource};
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableAddedOnlyTrait};
use Unilend\Filter\CountFilter;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"acceptationsLegalDocs:read", "timestampable:read"}},
 *     denormalizationContext={"groups": {"acceptationsLegalDocs:write"}},
 *     collectionOperations={
 *         "get",
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
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_legal_doc", "added_by"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\AcceptationLegalDocsRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity(fields={"legalDoc", "addedBy"}, message="AcceptationsLegalDocs.legalDoc.unique")
 */
class AcceptationsLegalDocs
{
    use TimestampableAddedOnlyTrait;
    use PublicizeIdentityTrait;
    use BlamableAddedTrait;

    /**
     * @var LegalDocument
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\LegalDocument")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_legal_doc", nullable=false)
     * })
     *
     * @Assert\Expression(
     *     "this.getLegalDoc().getId() === constant('Unilend\\Entity\\LegalDocument::CURRENT_SERVICE_TERMS')",
     *     message="AcceptationsLegalDocs.legalDoc.notCurrent"
     * )
     *
     * @Groups({"acceptationsLegalDocs:read", "acceptationsLegalDocs:write"})
     */
    private LegalDocument $legalDoc;

    /**
     * @param Staff         $addedBy
     * @param LegalDocument $legalDoc
     */
    public function __construct(Staff $addedBy, LegalDocument $legalDoc)
    {
        $this->legalDoc = $legalDoc;
        $this->addedBy  = $addedBy;
        $this->added    = new DateTimeImmutable();
    }

    /**
     * @return LegalDocument
     */
    public function getLegalDoc(): LegalDocument
    {
        return $this->legalDoc;
    }
}
