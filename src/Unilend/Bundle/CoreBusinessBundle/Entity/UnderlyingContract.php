<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UnderlyingContract
 *
 * @ORM\Table(name="underlying_contract", uniqueConstraints={@ORM\UniqueConstraint(name="unq_underlying_contract_label", columns={"label"})})
 * @ORM\Entity
 */
class UnderlyingContract
{
    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="document_template", type="string", length=191, nullable=false)
     */
    private $documentTemplate;

    /**
     * @var string
     *
     * @ORM\Column(name="block_slug", type="string", length=191, nullable=false)
     */
    private $blockSlug;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_contract", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idContract;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Product", mappedBy="idContract")
     */
    private $idProduct;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->idProduct = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Set label
     *
     * @param string $label
     *
     * @return UnderlyingContract
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set documentTemplate
     *
     * @param string $documentTemplate
     *
     * @return UnderlyingContract
     */
    public function setDocumentTemplate($documentTemplate)
    {
        $this->documentTemplate = $documentTemplate;

        return $this;
    }

    /**
     * Get documentTemplate
     *
     * @return string
     */
    public function getDocumentTemplate()
    {
        return $this->documentTemplate;
    }

    /**
     * Set blockSlug
     *
     * @param string $blockSlug
     *
     * @return UnderlyingContract
     */
    public function setBlockSlug($blockSlug)
    {
        $this->blockSlug = $blockSlug;

        return $this;
    }

    /**
     * Get blockSlug
     *
     * @return string
     */
    public function getBlockSlug()
    {
        return $this->blockSlug;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return UnderlyingContract
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
     * @return UnderlyingContract
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
     * Get idContract
     *
     * @return integer
     */
    public function getIdContract()
    {
        return $this->idContract;
    }

    /**
     * Add idProduct
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Product $idProduct
     *
     * @return UnderlyingContract
     */
    public function addIdProduct(\Unilend\Bundle\CoreBusinessBundle\Entity\Product $idProduct)
    {
        $this->idProduct[] = $idProduct;

        return $this;
    }

    /**
     * Remove idProduct
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Product $idProduct
     */
    public function removeIdProduct(\Unilend\Bundle\CoreBusinessBundle\Entity\Product $idProduct)
    {
        $this->idProduct->removeElement($idProduct);
    }

    /**
     * Get idProduct
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIdProduct()
    {
        return $this->idProduct;
    }
}
