<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Interfaces\TwigTemplateInterface;
use Unilend\Entity\Traits\MailPartTrait;

/**
 * Class MailFooter.
 *
 * @ORM\Entity
 */
class MailFooter implements TwigTemplateInterface
{
    use MailPartTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id", type="integer")
     */
    private $id;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
