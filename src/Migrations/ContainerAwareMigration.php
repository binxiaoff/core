<?php

declare(strict_types=1);

namespace Unilend\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\{ContainerAwareInterface, ContainerInterface};

abstract class ContainerAwareMigration extends AbstractMigration implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
