<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\User;
use KLS\Core\Mailer\MailjetMessage;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:borrowerMember:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:borrowerMember:create",
 *                     "agency:borrowerMember:write",
 *                     "user:create",
 *                     "user:write",
 *                 },
 *                 "openapi_definition_name": "write",
 *             },
 *         },
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": NotFoundAction::class,
 *             "read": false,
 *             "output": false,
 *             "openapi_context": {
 *                 "x-visibility": "hide",
 *             },
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:borrowerMember:write",
 *                 },
 *                 "openapi_definition_name": "item-patch-write",
 *             },
 *         },
 *     },
 * )
 *
 * @ORM\Table(name="agency_borrower_member", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"id_user", "id_borrower"})
 * })
 * @ORM\Entity
 *
 * @UniqueEntity(fields={"borrower", "user"})
 *
 * @ApiFilter(
 *     filterClass=GroupFilter::class,
 *     arguments={
 *         "whitelist": {
 *             "user:read"
 *         }
 *     }
 * )
 */
class BorrowerMember extends AbstractProjectMember
{
    /**
     * @ORM\ManyToOne(targetEntity=Borrower::class, inversedBy="members")
     * @ORM\JoinColumn(name="id_borrower", onDelete="CASCADE", nullable=false)
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:borrowerMember:read", "agency:borrowerMember:create"})
     *
     * @ApiProperty(readableLink=false)
     */
    private Borrower $borrower;

    public function __construct(Borrower $borrower, User $user)
    {
        parent::__construct($user);
        $this->borrower = $borrower;
    }

    public function getBorrower(): Borrower
    {
        return $this->borrower;
    }

    public function getProject(): Project
    {
        return $this->getBorrower()->getProject();
    }

    /**
     * @Groups({"agency:borrowerMember:read"})
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @Groups({"agency:borrowerMember:create"})
     */
    public function setUser(User $user): AbstractProjectMember
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @Groups({"agency:borrowerMember:read"})
     */
    public function getProjectFunction(): ?string
    {
        return $this->projectFunction;
    }

    /**
     * @Groups({"agency:borrowerMember:write"})
     */
    public function setProjectFunction(?string $projectFunction): AbstractProjectMember
    {
        $this->projectFunction = $projectFunction;

        return $this;
    }

    /**
     * @Groups({"agency:borrowerMember:read"})
     */
    public function isReferent(): bool
    {
        return $this->referent;
    }

    /**
     * @Groups({"agency:borrowerMember:write"})
     */
    public function setReferent(bool $referent): AbstractProjectMember
    {
        $this->referent = $referent;

        return $this;
    }

    /**
     * @Groups({"agency:borrowerMember:read"})
     */
    public function isSignatory(): bool
    {
        return $this->signatory;
    }

    /**
     * @Groups({"agency:borrowerMember:write"})
     */
    public function setSignatory(bool $signatory): AbstractProjectMember
    {
        $this->signatory = $signatory;

        return $this;
    }

    /**
     * @Groups({"agency:borrowerMember:read"})
     */
    public function isArchived(): bool
    {
        return parent::isArchived();
    }

    public static function getProjectPublicationNotificationMailjetTemplateId(): int
    {
        return MailjetMessage::TEMPLATE_AGENCY_BORROWER_MEMBER_PROJECT_PUBLISHED;
    }

    public function getProjectFrontUrl(RouterInterface $router): string
    {
        return $router->generate(
            'front_agencyBorrowerProjectView',
            ['projectPublicId' => $this->getProject()->getPublicId(), 'borrowerPublicId' => $this->getPublicId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
