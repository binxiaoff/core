<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
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
 *                 "groups": {
 *                     "agency:borrowerMember:create",
 *                     "agency:borrowerMember:write",
 *                     "user:create",
 *                     "user:write"
 *                 }
 *             }
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": NotFoundAction::class,
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:borrowerMember:write"
 *                 }
 *             }
 *         }
 *     }
 * )
 *
 * @ORM\Table(name="agency_borrower_member", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"id_user", "id_borrower"})
 * })
 * @ORM\Entity
 *
 * @UniqueEntity(fields={"borrower", "user"})
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

    public function getProject(): Project
    {
        return $this->borrower->getProject();
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

}
