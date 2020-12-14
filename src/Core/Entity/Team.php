<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ApiResource(
 *     attributes={
 *         "route_prefix"="/core"
 *     },
 *     normalizationContext={"groups": {"team:read"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {
 *              "security_post_denormalize": "is_granted('edit', object)",
 *              "denormalization_context": {"groups": {"team:update"}}
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {"groups": {"team:create"}}
 *         }
 *     }
 * )
 * @ORM\Table(name="core_team")
 * @ORM\Entity
 */
class Team
{
    use PublicizeIdentityTrait;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     *
     * @Groups({"team:create", "team:update", "team:read"})
     */
    private string $name;

    /**
     * @var Company|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Core\Entity\Company", mappedBy="rootTeam", fetch="EAGER")
     */
    private ?Company $company;

    /**
     * @var iterable
     *
     * @ORM\OneToMany(targetEntity="Unilend\Core\Entity\Staff", mappedBy="team")
     */
    private iterable $staff;

    /**
     * @var Team|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Team", inversedBy="children", fetch="EAGER")
     * @ORM\JoinColumn(name="id_parent", nullable=true)
     *
     * @Groups({"team:create", "team:read"})
     */
    private ?Team $parent;

    /**
     * @var iterable|Team[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Core\Entity\Team", mappedBy="parent")
     */
    private iterable $children;

    /**
     * Constructor
     *
     * Private to ensure correct object creation via static method
     */
    private function __construct()
    {
        $this->name = '';
        $this->parent = null;
        $this->company = null;
        $this->children = new ArrayCollection();
        $this->staff = new ArrayCollection();
    }

    /**
     * @param $name
     * @param Team $parent
     *
     * @return Team
     */
    public static function createTeam($name, Team $parent): Team
    {
        $team = new Team();
        $team->name = $name;
        $team->parent = $parent;
        $team->children->add($team);

        return $team;
    }

    /**
     * @param Company $company
     *
     * @return Team
     */
    public static function createRootTeam(Company $company): Team
    {
        $team = new Team();
        $team->company = $company;
        $team->name = 'root';

        return $team;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Company
     */
    public function getCompany(): Company
    {
        return $this->isRoot() ? $this->company : $this->getRoot()->getCompany();
    }

    /**
     * @return Team|null
     */
    public function getParent(): ?Team
    {
        return $this->parent;
    }

    /**
     * @return iterable|Staff[]
     */
    public function getStaff(): iterable
    {
        return $this->staff;
    }

    /**
     * @return iterable|Team[]
     */
    public function getChildren(): iterable
    {
        return $this->children;
    }

    /**
     * @return iterable|Team[]
     */
    public function getDescendents(): iterable
    {
        yield from $this->children;

        foreach ($this->children as $child) {
            yield from $child->getDescendents();
        }
    }

    /**
     * @return Team
     */
    public function getRoot(): Team
    {
        $team = $this;

        while (null !== $team->getParent()) {
            $team = $team->getParent();
        }

        return $team;
    }

    /**
     * @return bool
     */
    public function isRoot()
    {
        return null === $this->parent;
    }

    /**
     * @param string $name
     *
     * @return Team
     */
    public function setName(string $name): Team
    {
        $this->name = $this->isRoot() ? $this->name : $name;

        return $this;
    }
}
