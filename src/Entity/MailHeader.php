<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\MailPartTrait;

/**
 * Class MailHeader.
 *
 * @ORM\Entity
 */
class MailHeader
{
    use MailPartTrait;
}
