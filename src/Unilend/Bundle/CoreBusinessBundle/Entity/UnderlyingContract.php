<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * UnderlyingContract
 *
 * @ORM\Table(name="underlying_contract")
 * @ORM\Entity
 */
class UnderlyingContract
{
    const CONTRACT_IFP     = 'ifp';
    const CONTRACT_BDC     = 'bon_de_caisse';
    const CONTRACT_MINIBON = 'minibon';

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false, unique=true)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="document_template", type="string", length=191)
     */
    private $documentTemplate;

    /**
     * @var string
     *
     * @ORM\Column(name="block_slug", type="string", length=191)
     */
    private $blockSlug;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_contract", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idContract;

    /**
     * @var ProductUnderlyingContract[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProductUnderlyingContract", mappedBy="idContract", fetch="EXTRA_LAZY")
     */
    private $productContract;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->productContract = new ArrayCollection();
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
     * @return ProductUnderlyingContract[]
     */
    public function getProductContract(): array
    {
        return $this->productContract;
    }
}
