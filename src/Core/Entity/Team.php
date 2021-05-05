<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ApiResource(
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
 *             "denormalization_context": {"groups": {"team:create"}},
 *             "input": "Unilend\Core\DTO\Team\CreateTeam"
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
     *
     * @Groups({"team:read"})
     *
     * @MaxDepth(1)
     */
    private iterable $staff;

    /**
     * @var TeamEdge[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Core\Entity\TeamEdge", mappedBy="ancestor")
     */
    private Collection $outgoingEdges;

    /**
     * @var TeamEdge[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Core\Entity\TeamEdge", mappedBy="descendent", cascade={"persist"}, indexBy="depth")
     *
     * @Assert\Unique
     * @Assert\Valid
     */
    private Collection $incomingEdges;

    /**
     * Constructor
     *
     * Private to ensure correct object creation via static method
     */
    private function __construct()
    {
        $this->name = '';
        $this->company = null;
        $this->outgoingEdges = new ArrayCollection();
        $this->incomingEdges = new ArrayCollection();
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

        $edge = new TeamEdge($parent, $team, 1);
        $team->incomingEdges[1] = $edge;
        $parent->outgoingEdges[] = $edge;

        foreach ($parent->getAncestors() as $depth => $ancestor) {
            $edge = new TeamEdge($ancestor, $team, $depth + 1);
            $team->incomingEdges[$depth + 1] = $edge;
            $ancestor->outgoingEdges[] = $edge;
        }

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
        $team->name = $company->getDisplayName();

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
     * @return Team[]|array
     */
    public function getAncestors(): array
    {
        return $this->incomingEdges->map(fn (TeamEdge $edge) => $edge->getAncestor())->toArray();
    }

    /**
     * @return Team[]|array
     */
    public function getDescendents(): array
    {
        return $this->outgoingEdges->map(fn (TeamEdge $edge) => $edge->getDescendent())->toArray();
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
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"team:read"})
     */
    public function getParent(): ?Team
    {
        return false === $this->isRoot() ? $this->incomingEdges[1]->getAncestor() : null;
    }

    /**
     * @return array
     */
    public function getChildren(): array
    {
        return $this->outgoingEdges->filter(fn (TeamEdge $edge) => 1 === $edge->getDepth())->map(fn (TeamEdge $edge) => $edge->getDescendent())->toArray();
    }

    /**
     * @return Staff[]|iterable
     */
    public function getStaff(): iterable
    {
        return $this->staff;
    }

    /**
     * @return Team
     */
    public function getRoot(): Team
    {
        if ($this->isRoot()) {
            return $this;
        }

        $depth = max($this->incomingEdges->map(fn (TeamEdge $edge) => $edge->getDepth())->toArray());

        return $this->incomingEdges[$depth]->getAncestor();
    }

    /**
     * @return bool
     */
    public function isRoot()
    {
        return 0 === count($this->incomingEdges);
    }

    /**
     * @param string $name
     *
     * @return Team
     */
    public function setName(string $name): Team
    {
        $this->name = $name;

        return $this;
    }
}
