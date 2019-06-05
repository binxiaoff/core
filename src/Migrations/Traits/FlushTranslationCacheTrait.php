<?php

declare(strict_types=1);

namespace Unilend\Migrations\Traits;

use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait FlushTranslationCacheTrait
{
    /** @var ContainerInterface */
    protected $container;

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
