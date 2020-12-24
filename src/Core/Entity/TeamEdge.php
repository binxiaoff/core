<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="core_team_edge",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *          columns={"id_ancestor", "id_descendent"},
 *          name="uniq_team_edge_ancestor_descendent"
 *        ),
 *        @ORM\UniqueConstraint(
 *          columns={"id_descendent", "depth"},
 *          name="uniq_team_edge_descendent_depth"
 *        ),
 *    }
 * )
 *
 * @UniqueEntity(fields={"ancestor", "descendent"})
 * @UniqueEntity(fields={"descendent", "depth"})
 */
class TeamEdge
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @var Team
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Team", inversedBy="outgoingEdges")
     * @ORM\JoinColumn(name="id_ancestor")
     *
     * @Assert\NotBlank
     */
    private Team $ancestor;

    /**
     * @var Team
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Team", inversedBy="incomingEdges")
     * @ORM\JoinColumn(name="id_descendent")
     *
     * @Assert\NotBlank
     * @Assert\NotIdenticalTo(propertyPath="ancestor")
     */
    private Team $descendent;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     *
     * @Assert\NotBlank
     * @Assert\Positive
     */
    private int $depth;

    /**
     * @param Team $ancestor
     * @param Team $descendent
     * @param int  $depth
     */
    public function __construct(Team $ancestor, Team $descendent, int $depth)
    {
        $this->ancestor = $ancestor;
        $this->descendent = $descendent;
        $this->depth = $depth;
    }

    /**
     * @return Team
     */
    public function getAncestor(): Team
    {
        return $this->ancestor;
    }

    /**
     * @return Team
     */
    public function getDescendent(): Team
    {
        return $this->descendent;
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }
}
