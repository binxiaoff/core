<?php
declare(strict_types=1);

namespace Unilend\Bundle\CoreBusinessBundle\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable
 */
class LendingRate
{
    const INDEX_FIXED   = 'FIXED';
    const INDEX_EURIBOR = 'EURIBOR';
    const INDEX_EONIA   = 'EONIA';
    const INDEX_SONIA   = 'SONIA';
    const INDEX_LIBOR   = 'LIBOR';
    const INDEX_CHFTOIS = 'CHFTOIS';
    const INDEX_FFER    = 'FFER';

    const MARGIN_SCALE = 2;

    /**
     * @var string
     *
     * @ORM\Column(length=20)
     *
     * @Assert\NotBlank()
     */
    private $indexType;

    /**
     * The margin to be added on the indexed rate.
     *
     * @var string
     *
     * @ORM\Column(type="decimal", precision=4, scale=2)
     *
     * @Assert\NotBlank()
     * @Assert\Range(min="0.01", max="99.99")
     *
     */
    private $margin;

    /**
     * @return string|null
     */
    public function getIndexType(): ?string
    {
        return $this->indexType;
    }

    /**
     * @param string $indexType
     *
     * @return self
     */
    public function setIndexType(string $indexType): self
    {
        $this->indexType = $indexType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMargin(): ?string
    {
        return $this->margin;
    }

    /**
     * @param string $margin
     *
     * @return self
     */
    public function setMargin(string $margin): self
    {
        $this->margin = $margin;

        return $this;
    }

    static function getIndexes()
    {
        try {
            $self      = new \ReflectionClass(__CLASS__);
            $constants = $self->getConstants();
        } catch (\ReflectionException $exception) {
            return [];
        }
        $indexPrefix = 'INDEX_';
        $indexes     = array_filter(
            $constants,
            function($key) use ($indexPrefix) {
                return $indexPrefix === substr($key, 0, strlen($indexPrefix));
            },
            ARRAY_FILTER_USE_KEY
        );

        return $indexes;
    }
}
