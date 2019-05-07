<?php

namespace Unilend\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Table(name="underlying_contract")
 * @ORM\Entity
 */
class UnderlyingContract
{
    use TimestampableTrait;

    public const CONTRACT_IFP                = 'ifp';
    public const CONTRACT_BDC                = 'bon_de_caisse';
    public const CONTRACT_MINIBON            = 'minibon';
    public const CONTRACT_SOUS_PARTICIPATION = 'sous_participation';

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
     * @ORM\Column(name="block_slug", type="string", length=191, nullable=true)
     */
    private $blockSlug;

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
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProductUnderlyingContract", mappedBy="idContract", fetch="EXTRA_LAZY")
     */
    private $productContract;

    /**
     * UnderlyingContract constructor.
     */
    public function __construct()
    {
        $this->productContract = new ArrayCollection();
    }

    /**
     * @param string $label
     *
     * @return UnderlyingContract
     */
    public function setLabel(string $label): UnderlyingContract
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $documentTemplate
     *
     * @return UnderlyingContract
     */
    public function setDocumentTemplate(string $documentTemplate): UnderlyingContract
    {
        $this->documentTemplate = $documentTemplate;

        return $this;
    }

    /**
     * @return string
     */
    public function getDocumentTemplate(): string
    {
        return $this->documentTemplate;
    }

    /**
     * @param string $blockSlug
     *
     * @return UnderlyingContract
     */
    public function setBlockSlug(string $blockSlug): UnderlyingContract
    {
        $this->blockSlug = $blockSlug;

        return $this;
    }

    /**
     * @return string
     */
    public function getBlockSlug(): string
    {
        return $this->blockSlug;
    }

    /**
     * @return int
     */
    public function getIdContract(): int
    {
        return $this->idContract;
    }

    /**
     * @return ProductUnderlyingContract[]
     */
    public function getProductContract(): iterable
    {
        return $this->productContract;
    }
}
