<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use KLS\Core\Entity\User;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $archivingDate;

    /**
     * Field used to record the staff archiving the member
     * Not used in front only kept here for now for audit purposes.
     * User is used because of borrowers.
     *
     * @ORM\OneToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=true)
     *
     * @Assert\AtLeastOneOf({
     *     @Assert\IsNull,
     *     @Assert\Expression("value and this.isArchived()")
     * }, message="Agency.AbstractProjectMember.archiver.archived")
     */
    private ?User $archiver;

    public function __construct(User $user)
    {
        $this->user            = $user;
        $this->added           = new DateTimeImmutable();
        $this->referent        = false;
        $this->signatory       = false;
        $this->projectFunction = null;
        $this->archivingDate   = null;
        $this->archiver        = null;
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

    public function getArchivingDate(): ?DateTimeImmutable
    {
        return $this->archivingDate;
    }

    public function archive(?User $archiver = null): void
    {
        $this->archivingDate = new DateTimeImmutable();
        $this->archiver      = $archiver;
    }

    public function isArchived(): bool
    {
        return null !== $this->archivingDate;
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context)
    {
        if ($this->isArchived() && $this->getProject()->isDraft()) {
            $context->buildViolation('Agency.AbstractProjectMember.archived.draft')
                ->atPath('archived')
                ->addViolation()
            ;
        }

        if ($this->isArchived() && $this->isReferent()) {
            $context->buildViolation('Agency.AbstractProjectMember.archived.referent')
                ->atPath('archived')
                ->addViolation()
            ;
        }

        if ($this->isArchived() && $this->isSignatory()) {
            $context->buildViolation('Agency.AbstractProjectMember.archived.signatory')
                ->atPath('archived')
                ->addViolation()
            ;
        }
    }

    abstract public static function getProjectPublicationNotificationMailjetTemplateId(): int;

    abstract public function getProjectFrontUrl(RouterInterface $router): string;
}
