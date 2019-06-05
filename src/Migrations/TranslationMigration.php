<?php

declare(strict_types=1);

namespace Unilend\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\{ContainerAwareInterface, ContainerInterface};

abstract class TranslationMigration extends AbstractMigration implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema): void
    {
        $this->container->get('sonata.cache.symfony')->flush(['translations']);
    }

    /**
     * @param Schema $schema
     */
    public function postDown(Schema $schema): void
    {
        $this->container->get('sonata.cache.symfony')->flush(['translations']);
    }
}
