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
    const INDICE_FIXED   = 'FIXED';
    const INDICE_EURIBOR = 'EURIBOR';
    const INDICE_EONIA   = 'EONIA';
    const INDICE_SONIA   = 'SONIA';
    const INDICE_LIBOR   = 'LIBOR';
    const INDICE_CHFTOIS = 'CHFTOIS';
    const INDICE_FFER    = 'FFER';

    /**
     * @var string
     *
     * @ORM\Column(length=20)
     *
     * @Assert\NotBlank()
     */
    private $indice;

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
    public function getIndice(): ?string
    {
        return $this->indice;
    }

    /**
     * @param string $indice
     *
     * @return self
     */
    public function setIndice(string $indice): self
    {
        $this->indice = $indice;

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

    static function getIndices()
    {
        try {
            $self      = new \ReflectionClass(__CLASS__);
            $constants = $self->getConstants();
        } catch (\ReflectionException $exception) {
            return [];
        }
        $indicePrefix = 'INDICE_';
        $indices      = array_filter(
            $constants,
            function($key) use ($indicePrefix) {
                return $indicePrefix === substr($key, 0, strlen($indicePrefix));
            },
            ARRAY_FILTER_USE_KEY
        );

        return $indices;
    }
}
