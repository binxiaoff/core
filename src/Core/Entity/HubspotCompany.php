<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Entity
 *
 * @ORM\Table(name="core_hubspot_company")
 */
class HubspotCompany
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
    private int $companyId;

    public function __construct(User $user, int $companyId)
    {
        $this->user      = $user;
        $this->companyId = $companyId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): HubspotCompany
    {
        $this->user = $user;

        return $this;
    }

    public function getCompanyId(): int
    {
        return $this->companyId;
    }

    public function setCompanyId(int $companyId): HubspotCompany
    {
        $this->companyId = $companyId;

        return $this;
    }
}
