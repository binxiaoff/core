<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_drive")
 */
class Drive extends AbstractFileContainer
{
    /**
     * @var Folder[]|Collection
     *
     * @ORM\OneToMany(targetEntity=Folder::class, indexBy="path", mappedBy="drive")
     */
    private Collection $folders;

    /**
     * construct
     */
    public function __construct()
    {
        parent::__construct();
        $this->folders = new ArrayCollection();
    }

    /**
     * @return Collection|Folder[]
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * @param string      $name
     * @param string|null $path
     *
     * @return Folder
     */
    public function createFolder(string $name, ?string $path = null)
    {
        $folder = new Folder($name, $this, $path);

        $this->folders[$folder->getPath()] = $folder;

        return $folder;
    }

    /**
     * @param File $file
     *
     * @return $this
     */
    public function addFile(File $file): Drive
    {
        $this->files[] = $file;

        return $this;
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function isPathPresent(string $path): bool
    {
        return '/' === $path || isset($this->folders[$path]);
    }
}
