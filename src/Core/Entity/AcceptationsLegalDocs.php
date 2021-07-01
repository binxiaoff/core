<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Core\Filter\CountFilter;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"acceptationsLegalDocs:read", "timestampable:read"}},
 *     denormalizationContext={"groups": {"acceptationsLegalDocs:write"}},
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)"
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
 *         @ORM\UniqueConstraint(columns={"id_legal_doc", "accepted_by"})
 *     },
 *     name="core_acceptations_legal_docs"
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity(fields={"legalDoc", "acceptedBy"}, message="Core.AcceptationsLegalDocs.legalDoc.unique")
 */
class AcceptationsLegalDocs
{
    use TimestampableAddedOnlyTrait;
    use PublicizeIdentityTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\LegalDocument")
     * @ORM\JoinColumn(name="id_legal_doc", nullable=false)
     *
     * TODO CALS-4049 This validation should be moved elsewhere. Once another legal document has been created the previous acceptation legal doc are not invalid.
     * @Assert\Expression(
     *     "this.getLegalDoc().getId() === constant('Unilend\\Core\\Entity\\LegalDocument::CURRENT_SERVICE_TERMS_ID')",
     *     message="Core.AcceptationsLegalDocs.legalDoc.notCurrent"
     * )
     *
     * @Groups({"acceptationsLegalDocs:read", "acceptationsLegalDocs:write"})
     */
    private LegalDocument $legalDoc;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\User", inversedBy="legalDocumentAcceptations")
     * @ORM\JoinColumn(name="accepted_by", nullable=false)
     */
    private User $acceptedBy;

    /**
     * @throws Exception
     */
    public function __construct(User $acceptedBy, LegalDocument $legalDoc)
    {
        $this->legalDoc   = $legalDoc;
        $this->acceptedBy = $acceptedBy;
        $this->added      = new DateTimeImmutable();
    }

    public function getLegalDoc(): LegalDocument
    {
        return $this->legalDoc;
    }

    public function getAcceptedBy(): User
    {
        return $this->acceptedBy;
    }
}
