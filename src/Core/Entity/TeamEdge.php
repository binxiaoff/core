<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents an edge in the company arborescence.
 * Its usage is purely internal. It is not meant to be manipulated via an http request.
 * Prefer to create and use method on @see Team class.
 * Should a two @see Team be accessible, there should be a TeamEdge for them.
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="core_team_edge",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             columns={"id_ancestor", "id_descendent"},
 *             name="uniq_team_edge_ancestor_descendent"
 *         ),
 *         @ORM\UniqueConstraint(
 *             columns={"id_descendent", "depth"},
 *             name="uniq_team_edge_descendent_depth"
 *         ),
 *     }
 * )
 *
 * @UniqueEntity(fields={"ancestor", "descendent"})
 * @UniqueEntity(fields={"descendent", "depth"})
 */
class TeamEdge
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * Head of the edge.
     *
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Team", inversedBy="outgoingEdges", fetch="EAGER")
     * @ORM\JoinColumn(name="id_ancestor")
     *
     * @Assert\NotBlank
     */
    private Team $ancestor;

    /**
     * Tail of the edge.
     *
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Team", inversedBy="incomingEdges")
     * @ORM\JoinColumn(name="id_descendent")
     *
     * @Assert\NotBlank
     * @Assert\NotIdenticalTo(propertyPath="ancestor")
     */
    private Team $descendent;

    /**
     * Number of nodes between the head and the tail in the arborecense.
     *
     * @ORM\Column(type="integer", nullable=false)
     *
     * @Assert\NotBlank
     * @Assert\Positive
     */
    private int $depth;

    public function __construct(Team $ancestor, Team $descendent, int $depth)
    {
        $this->ancestor   = $ancestor;
        $this->descendent = $descendent;
        $this->depth      = $depth;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAncestor(): Team
    {
        return $this->ancestor;
    }

    public function getDescendent(): Team
    {
        return $this->descendent;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }
}
