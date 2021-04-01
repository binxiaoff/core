<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;
use Unilend\Core\Entity\User;
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "timestampable:read",
 *             "agency:borrowerMember:read"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                "groups": {"agency:borrowerMember:create", "user:create", "user:write"}
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
 */
class BorrowerMember
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @var Borrower
     *
     * @ORM\ManyToOne(targetEntity=Borrower::class, inversedBy="members")
     * @ORM\JoinColumn(name="id_borrower", onDelete="CASCADE")
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
     * @param User $user
     *
     * @return BorrowerMember
     */
    public function setUser(User $user): BorrowerMember
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Borrower
     */
    public function getBorrower(): Borrower
    {
        return $this->borrower;
    }
}
