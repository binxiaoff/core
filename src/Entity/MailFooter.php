<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Interfaces\TwigTemplateInterface;
use Unilend\Entity\Traits\MailPartTrait;

/**
 * Class MailFooter.
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
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
    private int $id;

    /**
     * @param string $name
     * @param string $locale
     */
    public function __construct(string $name, string $locale = 'fr_FR')
    {
        $this->added = new DateTimeImmutable();
        $this->name = $name;
        $this->locale = $locale;
    }
    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
