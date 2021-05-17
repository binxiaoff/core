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
 *             "agency:borrowerMember:read",
 *             "agency:projectMember:read"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {"agency:borrowerMember:create", "agency:projectMember:write", "agency:projectMember:create", "user:create", "user:write"}
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

    public function getProject(): Project
    {
        return $this->borrower->getProject();
    }

    public function getBorrower(): Borrower
    {
        return $this->borrower;
    }
}
