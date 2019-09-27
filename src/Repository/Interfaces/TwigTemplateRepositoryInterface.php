<?php

declare(strict_types=1);

namespace Unilend\Repository\Interfaces;

use Doctrine\Common\Persistence\ObjectRepository;
use Unilend\Entity\Interfaces\TwigTemplateInterface;

/**
 * @method TwigTemplateInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method TwigTemplateInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method TwigTemplateInterface[]    findAll()
 * @method TwigTemplateInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
interface TwigTemplateRepositoryInterface extends ObjectRepository
{
}
