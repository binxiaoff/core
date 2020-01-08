<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\Permission;
use Unilend\Entity\Traits\{BlamableAddedTrait, RoleableTrait, TimestampableTrait};

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"projectOrganizer:read", "role:read", "company:read"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "delete": {"security": "is_granted('edit', object.getProject())"},
 *         "patch": {"security": "is_granted('edit', object.getProject())", "groups": {"projectOrganizer:write", "role:write"}}
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('edit', object.getProject())",
 *             "denormalization_context": {"groups": {"projectOrganizer:create", "role:write"}}
 *         },
 *     }
 * )
 * @ORM\Entity
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"id_project", "id_company"})
 *     }
 * )
 *
 * @UniqueEntity(fields={"project", "company"})
 */
class ProjectOrganizer
{
    use BlamableAddedTrait;
    use RoleableTrait;
    use TimestampableTrait;

    public const DUTY_PROJECT_ORGANIZER_ARRANGER         = 'arranger'; // The company who arranges a loan syndication.
    public const DUTY_PROJECT_ORGANIZER_DEPUTY_ARRANGER  = 'deputy_arranger';
    public const DUTY_PROJECT_ORGANIZER_RUN              = 'run'; // Responsable Unique de Notation, who gives a note on the borrower.
    public const DUTY_PROJECT_ORGANIZER_LOAN_OFFICER     = 'loan_officer';
    public const DUTY_PROJECT_ORGANIZER_SECURITY_TRUSTEE = 'security_trustee';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"projectOrganizer:read"})
     */
    private $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="organizers")
     * @ORM\JoinColumn(name="id_project", nullable=false)
     *
     * @Assert\NotBlank
     *
     * @Groups({"projectOrganizer:read", "projectOrganizer:create"})
     */
    private $project;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumn(name="id_company", nullable=false)
     *
     * @Assert\NotBlank
     *
     * @Groups({"projectOrganizer:read", "projectOrganizer:create"})
     */
    private $company;

    /**
     * @var Permission
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Permission")
     */
    private $permission;

    /**
     * @param Companies             $company
     * @param Project               $project
     * @param Clients               $client
     * @param array|string[]|string $roles
     *
     * @throws Exception
     */
    public function __construct(Companies $company, Project $project, Clients $client, $roles = [])
    {
        $this->project    = $project;
        $this->company    = $company;
        $this->addedBy    = $client;
        $this->added      = new DateTimeImmutable();
        $this->permission = new Permission();

        $this->setRoles((array) $roles);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return Companies
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @return mixed
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * @param mixed $permission
     *
     * @return ProjectOrganizer
     */
    public function setPermission(Permission $permission): ProjectOrganizer
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * @param string $role
     *
     * @return bool
     */
    public static function isUniqueRole(string $role): bool
    {
        return \in_array($role, [
            static::DUTY_PROJECT_ORGANIZER_ARRANGER,
            static::DUTY_PROJECT_ORGANIZER_RUN,
            static::DUTY_PROJECT_ORGANIZER_LOAN_OFFICER,
            static::DUTY_PROJECT_ORGANIZER_SECURITY_TRUSTEE,
        ], true);
    }

    /**
     * @return bool
     */
    public function isArranger(): bool
    {
        return $this->hasRole(self::DUTY_PROJECT_ORGANIZER_ARRANGER);
    }

    /**
     * @return bool
     */
    public function isRun(): bool
    {
        return $this->hasRole(self::DUTY_PROJECT_ORGANIZER_RUN);
    }
}
