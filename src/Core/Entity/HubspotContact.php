<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 *
 * @ORM\Table(name="core_hubspot_contact")
 */
class HubspotContact
{
    use TimestampableAddedOnlyTrait;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @ORM\OneToOne(targetEntity=User::class)
     * @ORM\JoinColumn(name="id_user")
     *
     * @Assert\NotBlank
     */
    private User $user;

    /**
     * @ORM\Column(type="integer", nullable=false, name="id_contact")
     *
     * @Assert\Type("integer")
     * @Assert\Positive
     */
    private int $contactId;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $synchronized;

    public function __construct(User $user, int $contactId)
    {
        $this->user         = $user;
        $this->contactId    = $contactId;
        $this->added        = new DateTimeImmutable();
        $this->synchronized = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): HubspotContact
    {
        $this->user = $user;

        return $this;
    }

    public function getContactId(): int
    {
        return $this->contactId;
    }

    public function setContactId(int $contactId): HubspotContact
    {
        $this->contactId = $contactId;

        return $this;
    }

    public function getSynchronized(): DateTimeImmutable
    {
        return $this->synchronized;
    }

    public function setSynchronized(DateTimeImmutable $synchronized): HubspotContact
    {
        $this->synchronized = $synchronized;

        return $this;
    }
}
