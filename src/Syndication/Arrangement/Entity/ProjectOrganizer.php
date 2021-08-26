<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Core\Entity\Traits\RoleableTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "projectOrganizer:read",
 *             "role:read",
 *             "company:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *             "openapi_context": {
 *                 "x-visibility": "hide",
 *             },
 *         },
 *         "delete": {"security": "is_granted('delete', object)"},
 *         "patch": {"security": "is_granted('edit', object)", "groups": {"projectOrganizer:write", "role:write"}},
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "projectOrganizer:create",
 *                     "role:write",
 *                 },
 *                 "openapi_definition_name": "write",
 *             },
 *         },
 *     },
 * )
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="syndication_project_organizer",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"id_project", "id_company"})
 *     }
 * )
 *
 * @UniqueEntity(fields={"company", "project"})
 */
class ProjectOrganizer
{
    use BlamableAddedTrait;
    use RoleableTrait;
    use TimestampableTrait;

    public const DUTY_PROJECT_ORGANIZER_DEPUTY_ARRANGER = 'deputy_arranger';
    public const DUTY_PROJECT_ORGANIZER_RUN             = 'run'; // Responsable Unique de Notation, who gives a note on the borrower.
    public const DUTY_PROJECT_ORGANIZER_AGENT           = 'agent';

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
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Arrangement\Entity\Project", inversedBy="organizers")
     * @ORM\JoinColumn(name="id_project", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     *
     * @Groups({"projectOrganizer:read", "projectOrganizer:create"})
     */
    private $project;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Company")
     * @ORM\JoinColumn(name="id_company", nullable=false)
     *
     * @Assert\NotBlank
     *
     * @Groups({"projectOrganizer:read", "projectOrganizer:create"})
     */
    private $company;

    /**
     * Overriden here to disabled validation on role count.
     *
     * @var array
     *
     * @ORM\Column(type="json")
     *
     * @Groups({"role:read", "role:write"})
     *
     * @Assert\Choice(callback="getAvailableRoles", multiple=true, multipleMessage="Core.Roleable.roles.choice")
     * @Assert\Unique(message="Core.Roleable.roles.unique")
     */
    private $roles = [];

    /**
     * @param array|string[]|string $roles
     *
     * @throws Exception
     */
    public function __construct(Company $company, Project $project, Staff $addedBy, $roles = [])
    {
        $this->project = $project;
        $this->company = $company;
        $this->addedBy = $addedBy;
        $this->added   = new DateTimeImmutable();

        $this->setRoles((array) $roles);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    public static function isUniqueRole(string $role): bool
    {
        return \in_array($role, [
            static::DUTY_PROJECT_ORGANIZER_RUN,
            static::DUTY_PROJECT_ORGANIZER_AGENT,
        ], true);
    }

    /**
     * @Assert\Callback
     */
    public function validateUniqueRoles(ExecutionContextInterface $context): void
    {
        /** @var ProjectOrganizer[] $organizers */
        $organizers = $this->project->getOrganizers()->toArray();
        foreach ($this->roles as $role) {
            if (static::isUniqueRole($role)) {
                $organizer = \reset($organizers);
                // Search for already existing organizers with tested role
                while ($organizer && (false === $organizer->hasRole($role) || $organizer === $this)) {
                    $organizer = \next($organizers);
                }
                if ($organizer) {
                    $context->buildViolation('Syndication.ProjectOrganizer.roles.duplicatedUniqueRole')
                        ->atPath('roles')
                        ->setInvalidValue($role)
                        ->setParameters([
                            '{{ company }}' => $organizer->getCompany()->getDisplayName(),
                            '{{ value }}'   => $role,
                        ])
                        ->addViolation()
                    ;
                }
            }
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateRunRole(ExecutionContextInterface $context): void
    {
        if ($this->hasRole(self::DUTY_PROJECT_ORGANIZER_RUN) && false === $this->company->isCAGMember()) {
            $context->buildViolation('Syndication.ProjectOrganizer.roles.runRole')
                ->atPath('roles')
                ->addViolation()
            ;
        }
    }
}
