<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tree
 *
 * @ORM\Table(name="tree", indexes={@ORM\Index(name="id_parent", columns={"id_parent"}), @ORM\Index(name="id_template", columns={"id_template"}), @ORM\Index(name="id_tree", columns={"id_tree"})})
 * @ORM\Entity
 */
class Tree
{
    const STATUS_OFFLINE = 0;
    const STATUS_ONLINE  = 1;

    const VISIBILITY_PRIVATE = 1;
    const VISIBILITY_PUBLIC  = 0;

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
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

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
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idLangue;

    /**
     * Set idParent
     *
     * @param integer $idParent
     *
     * @return Tree
     */
    public function setIdParent($idParent)
    {
        $this->idParent = $idParent;

        return $this;
    }

    /**
     * Get idParent
     *
     * @return integer
     */
    public function getIdParent()
    {
        return $this->idParent;
    }

    /**
     * Set idTemplate
     *
     * @param integer $idTemplate
     *
     * @return Tree
     */
    public function setIdTemplate($idTemplate)
    {
        $this->idTemplate = $idTemplate;

        return $this;
    }

    /**
     * Get idTemplate
     *
     * @return integer
     */
    public function getIdTemplate()
    {
        return $this->idTemplate;
    }

    /**
     * Set idUser
     *
     * @param integer $idUser
     *
     * @return Tree
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return integer
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * Set title
     *
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
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set slug
     *
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
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set imgMenu
     *
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
     * Get imgMenu
     *
     * @return string
     */
    public function getImgMenu()
    {
        return $this->imgMenu;
    }

    /**
     * Set menuTitle
     *
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
     * Get menuTitle
     *
     * @return string
     */
    public function getMenuTitle()
    {
        return $this->menuTitle;
    }

    /**
     * Set metaTitle
     *
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
     * Get metaTitle
     *
     * @return string
     */
    public function getMetaTitle()
    {
        return $this->metaTitle;
    }

    /**
     * Set metaDescription
     *
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
     * Get metaDescription
     *
     * @return string
     */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * Set metaKeywords
     *
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
     * Get metaKeywords
     *
     * @return string
     */
    public function getMetaKeywords()
    {
        return $this->metaKeywords;
    }

    /**
     * Set ordre
     *
     * @param integer $ordre
     *
     * @return Tree
     */
    public function setOrdre($ordre)
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * Get ordre
     *
     * @return integer
     */
    public function getOrdre()
    {
        return $this->ordre;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Tree
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set statusMenu
     *
     * @param integer $statusMenu
     *
     * @return Tree
     */
    public function setStatusMenu($statusMenu)
    {
        $this->statusMenu = $statusMenu;

        return $this;
    }

    /**
     * Get statusMenu
     *
     * @return integer
     */
    public function getStatusMenu()
    {
        return $this->statusMenu;
    }

    /**
     * Set prive
     *
     * @param integer $prive
     *
     * @return Tree
     */
    public function setPrive($prive)
    {
        $this->prive = $prive;

        return $this;
    }

    /**
     * Get prive
     *
     * @return integer
     */
    public function getPrive()
    {
        return $this->prive;
    }

    /**
     * Set indexation
     *
     * @param integer $indexation
     *
     * @return Tree
     */
    public function setIndexation($indexation)
    {
        $this->indexation = $indexation;

        return $this;
    }

    /**
     * Get indexation
     *
     * @return integer
     */
    public function getIndexation()
    {
        return $this->indexation;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Tree
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return Tree
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set idTree
     *
     * @param integer $idTree
     *
     * @return Tree
     */
    public function setIdTree($idTree)
    {
        $this->idTree = $idTree;

        return $this;
    }

    /**
     * Get idTree
     *
     * @return integer
     */
    public function getIdTree()
    {
        return $this->idTree;
    }

    /**
     * Set idLangue
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
     * Get idLangue
     *
     * @return string
     */
    public function getIdLangue()
    {
        return $this->idLangue;
    }
}
