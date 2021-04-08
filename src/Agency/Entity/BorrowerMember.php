<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Core\Entity\User;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:borrowerMember:read"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                "groups": {"agency:borrowerMember:create", "agency:borrowerMember:write", "user:create", "user:write"}
 *             }
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": NotFoundAction::class,
 *             "read": false,
 *             "output": false,
 *         }
 *     }
 * )
 *
 * @ORM\Table(name="agency_borrower_member", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"id_user", "id_borrower"})
 * })
 * @ORM\Entity
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
class BorrowerMember
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    /**
     * @var Borrower
     *
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

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity=User::class, cascade={"persist"})
     * @ORM\JoinColumn(name="id_user")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:borrowerMember:read", "agency:borrowerMember:create"})
     */
    private User $user;

    /**
     * @var string|null
     *
     * @Groups({"agency:borrowerMember:read", "agency:borrowerMember:write"})
     *
     * @Assert\Length(max=200)
     *
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    protected ?string $projectFunction;

    /**
     * @param Borrower $borrower
     * @param User     $user
     */
    public function __construct(Borrower $borrower, User $user)
    {
        $this->added    = new DateTimeImmutable();
        $this->user     = $user;
        $this->borrower = $borrower;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->borrower->getProject();
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return Borrower
     */
    public function getBorrower(): Borrower
    {
        return $this->borrower;
    }

    /**
     * @return string|null
     */
    public function getProjectFunction(): ?string
    {
        return $this->projectFunction;
    }

    /**
     * @param string|null $projectFunction
     *
     * @return BorrowerMember
     */
    public function setProjectFunction(?string $projectFunction): BorrowerMember
    {
        $this->projectFunction = $projectFunction;

        return $this;
    }
}
