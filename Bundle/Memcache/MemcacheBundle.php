<?php
namespace Unilend\Bundle\Memcache;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Unilend\Bundle\Memcache\DependencyInjection\Compiler\EnableSessionSupport;

/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 20/04/2016
 * Time: 13:52
 */
class MemcacheBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new EnableSessionSupport());
    }
}