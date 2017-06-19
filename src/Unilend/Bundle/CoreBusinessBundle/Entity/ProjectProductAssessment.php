<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectProductAssessment
 *
 * @ORM\Table(name="project_product_assessment", indexes={@ORM\Index(name="idx_project_product_assessment_id_project", columns={"id_project"}), @ORM\Index(name="idx_project_product_assessment_id_product", columns={"id_product"}), @ORM\Index(name="idx_project_product_assessment_id_product_attribute_type", columns={"id_product_attribute_type"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectProductAssessment
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Product
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Product")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_product", referencedColumnName="id_product")
     * })
     */
    private $idProduct;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_product_attribute_type", referencedColumnName="id_type")
     * })
     */
    private $idProductAttributeType;

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return ProjectProductAssessment
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectProductAssessment
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set idProject
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Projects $idProject
     *
     * @return ProjectProductAssessment
     */
    public function setIdProject(\Unilend\Bundle\CoreBusinessBundle\Entity\Projects $idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * Set idProduct
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Product $idProduct
     *
     * @return ProjectProductAssessment
     */
    public function setIdProduct(\Unilend\Bundle\CoreBusinessBundle\Entity\Product $idProduct)
    {
        $this->idProduct = $idProduct;

        return $this;
    }

    /**
     * Get idProduct
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Product
     */
    public function getIdProduct()
    {
        return $this->idProduct;
    }

    /**
     * Set idProductAttributeType
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType $idProductAttributeType
     *
     * @return ProjectProductAssessment
     */
    public function setIdProductAttributeType(\Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType $idProductAttributeType)
    {
        $this->idProductAttributeType = $idProductAttributeType;

        return $this;
    }

    /**
     * Get idProductAttributeType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType
     */
    public function getIdProductAttributeType()
    {
        return $this->idProductAttributeType;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }
}
