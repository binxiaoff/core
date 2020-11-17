<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Symfony\Component\Validator\Constraints as Assert;

use Unilend\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 * @ORM\Table(
 *  name="message_thread",
 *  indexes={
 *     @ORM\Index(name="idx_added", columns={"added"})
 *  }
 * )
 * @ORM\HasLifecycleCallbacks
 */
class MessageThread
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    /**
     * @var ArrayCollection|Collection
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Message", mappedBy="messageThread")
     */
    private Collection $messages;

    /**
     * Thread constructor.
     */
    public function __construct()
    {
        $this->added = new \DateTimeImmutable();
        $this->messages = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param Message $message
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