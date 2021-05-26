<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Core\Entity\User;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractProjectMember
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    /**
     * @Assert\Length(max=200)
     *
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    protected ?string $projectFunction;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, cascade={"persist"})
     * @ORM\JoinColumn(name="id_user", nullable=false)
     *
     * @Assert\NotBlank
     * @Assert\Valid
     */
    protected User $user;

    /**
     * @ORM\Column(type="boolean")
     */
    protected bool $referent;

    /**
     * @ORM\Column(type="boolean")
     */
    protected bool $signatory;

    public function __construct(User $user)
    {
        $this->user      = $user;
        $this->added     = new DateTimeImmutable();
        $this->referent  = false;
        $this->signatory = false;
        $this->setPublicId();
    }

    abstract public function getProject(): Project;

    public function getUser(): User
    {
        return $this->user;
    }

    public function getProjectFunction(): ?string
    {
        return $this->projectFunction;
    }

    public function setProjectFunction(?string $projectFunction): AbstractProjectMember
    {
        $this->projectFunction = $projectFunction;

        return $this;
    }

    public function isReferent(): bool
    {
        return $this->referent;
    }

    public function setReferent(bool $referent): AbstractProjectMember
    {
        $this->referent = $referent;

        return $this;
    }

    public function isSignatory(): bool
    {
        return $this->signatory;
    }

    public function setSignatory(bool $signatory): AbstractProjectMember
    {
        $this->signatory = $signatory;

        return $this;
    }
}
