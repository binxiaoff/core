<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Table(name="tree", indexes={@ORM\Index(name="id_parent", columns={"id_parent"}), @ORM\Index(name="id_template", columns={"id_template"})})
 * @ORM\Entity
 */
class Tree
{
    use TimestampableTrait;

    public const STATUS_OFFLINE = 0;
    public const STATUS_ONLINE  = 1;

    public const VISIBILITY_PRIVATE = 1;
    public const VISIBILITY_PUBLIC  = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="id_parent", type="integer")
     */
    private $idParent;

    /**
     * @var int
     *
     * @ORM\Column(name="id_template", type="integer")
     */
    private $idTemplate;

    /**
     * @var int
     *
     * @ORM\Column(name="id_user", type="integer")
     */
    private $idUser;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=191)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=191)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="img_menu", type="string", length=191)
     */
    private $imgMenu;

    /**
     * @var string
     *
     * @ORM\Column(name="menu_title", type="string", length=191)
     */
    private $menuTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_title", type="string", length=191)
     */
    private $metaTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_description", type="text", length=16777215)
     */
    private $metaDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_keywords", type="text", length=16777215)
     */
    private $metaKeywords;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre", type="integer")
     */
    private $ordre = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="status_menu", type="integer")
     */
    private $statusMenu = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="prive", type="integer")
     */
    private $prive = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="indexation", type="integer")
     */
    private $indexation = '1';

    /**
     * @var int
     *
     * @ORM\Column(name="id_tree", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idTree;

    /**
     * @var string
     *
     * @ORM\Column(name="id_langue", type="string", length=2)
     */
    private $idLangue;

    /**
     * @param int $idParent
     *
     * @return Tree
     */
    public function setIdParent($idParent)
    {
        $this->idParent = $idParent;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdParent()
    {
        return $this->idParent;
    }

    /**
     * @param int $idTemplate
     *
     * @return Tree
     */
    public function setIdTemplate($idTemplate)
    {
        $this->idTemplate = $idTemplate;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdTemplate()
    {
        return $this->idTemplate;
    }

    /**
     * @param int $idUser
     *
     * @return Tree
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * @param string $title
     *
     * @return Tree
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $slug
     *
     * @return Tree
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $imgMenu
     *
     * @return Tree
     */
    public function setImgMenu($imgMenu)
    {
        $this->imgMenu = $imgMenu;

        return $this;
    }

    /**
     * @return string
     */
    public function getImgMenu()
    {
        return $this->imgMenu;
    }

    /**
     * @param string $menuTitle
     *
     * @return Tree
     */
    public function setMenuTitle($menuTitle)
    {
        $this->menuTitle = $menuTitle;

        return $this;
    }

    /**
     * @return string
     */
    public function getMenuTitle()
    {
        return $this->menuTitle;
    }

    /**
     * @param string $metaTitle
     *
     * @return Tree
     */
    public function setMetaTitle($metaTitle)
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    /**
     * @return string
     */
    public function getMetaTitle()
    {
        return $this->metaTitle;
    }

    /**
     * @param string $metaDescription
     *
     * @return Tree
     */
    public function setMetaDescription($metaDescription)
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    /**
     * @return string
     */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * @param string $metaKeywords
     *
     * @return Tree
     */
    public function setMetaKeywords($metaKeywords)
    {
        $this->metaKeywords = $metaKeywords;

        return $this;
    }

    /**
     * @return string
     */
    public function getMetaKeywords()
    {
        return $this->metaKeywords;
    }

    /**
     * @param int $ordre
     *
     * @return Tree
     */
    public function setOrdre($ordre)
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrdre()
    {
        return $this->ordre;
    }

    /**
     * @param int $status
     *
     * @return Tree
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $statusMenu
     *
     * @return Tree
     */
    public function setStatusMenu($statusMenu)
    {
        $this->statusMenu = $statusMenu;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusMenu()
    {
        return $this->statusMenu;
    }

    /**
     * @param int $prive
     *
     * @return Tree
     */
    public function setPrive($prive)
    {
        $this->prive = $prive;

        return $this;
    }

    /**
     * @return int
     */
    public function getPrive()
    {
        return $this->prive;
    }

    /**
     * @param int $indexation
     *
     * @return Tree
     */
    public function setIndexation($indexation)
    {
        $this->indexation = $indexation;

        return $this;
    }

    /**
     * @return int
     */
    public function getIndexation()
    {
        return $this->indexation;
    }

    /**
     * Set idTree.
     *
     * @param int $idTree
     *
     * @return Tree
     */
    public function setIdTree($idTree)
    {
        $this->idTree = $idTree;

        return $this;
    }

    /**
     * Get idTree.
     *
     * @return int
     */
    public function getIdTree()
    {
        return $this->idTree;
    }

    /**
     * Set idLangue.
     *
     * @param string $idLangue
     *
     * @return Tree
     */
    public function setIdLangue($idLangue)
    {
        $this->idLangue = $idLangue;

        return $this;
    }

    /**
     * Get idLangue.
     *
     * @return string
     */
    public function getIdLangue()
    {
        return $this->idLangue;
    }
}
