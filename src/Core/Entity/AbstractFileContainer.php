<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 */
abstract class AbstractFileContainer
{
    /**
     * @var File[]|Collection
     *
     * @ORM\ManyToMany(targetEntity=File::class)
     * @ORM\JoinTable(
     *     inverseJoinColumns={@ORM\JoinColumn(name="file_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected Collection $files;
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private ?int $id = null;

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->files = new ArrayCollection();
    }

    /**
     * @return Collection|File[]
     */
    public function getFiles(): iterable
    {
        return $this->files;
    }
}
