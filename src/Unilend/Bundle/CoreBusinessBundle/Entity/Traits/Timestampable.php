<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait Timestampable
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    protected $added;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    protected $updated;

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return self
     */
    public function setAdded(\DateTime $added): self
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime|null $updated
     *
     * @return self
     */
    public function setUpdated(?\DateTime $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue(): void
    {
        $this->updated = new \DateTime();
    }
}
