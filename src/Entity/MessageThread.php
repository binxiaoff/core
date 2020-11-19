<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Unilend\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 * @ORM\Table
 */
class MessageThread
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    /**
     * @var ArrayCollection|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Message", mappedBy="messageThread")
     */
    private Collection $messages;

    /**
     * MessageThread constructor.
     */
    public function __construct()
    {
        $this->added    = new DateTimeImmutable();
        $this->messages = new ArrayCollection();
    }

    /**
     * @return Message[]|Collection
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /**
     * @param Message $message
     *
     * @return MessageThread
     */
    public function addMessage(Message $message): MessageThread
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
        }

        return $this;
    }
}
