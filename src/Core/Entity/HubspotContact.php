<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Entity
 *
 * @ORM\Table(name="core_hubspot_contact")
 */
class HubspotContact
{
    use TimestampableTrait;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @ORM\OneToOne(targetEntity=User::class)
     *
     * @Assert\NotBlank
     */
    private User $user;

    /**
     * @ORM\Column(type="integer", nullable=false)
     *
     * @Assert\Type("integer")
     * @Assert\Positive
     */
    private int $contactId;

    public function __construct(User $user, int $contactId)
    {
        $this->user      = $user;
        $this->contactId = $contactId;
        $this->added     = new DateTimeImmutable();
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
}
