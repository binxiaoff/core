<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Unilend\Bundle\Monolog;

use Unilend\Bundle\Monolog\DependencyInjection\Compiler\AddSwiftMailerTransportPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Unilend\Bundle\Monolog\DependencyInjection\Compiler\LoggerChannelPass;
use Unilend\Bundle\Monolog\DependencyInjection\Compiler\DebugHandlerPass;
use Unilend\Bundle\Monolog\DependencyInjection\Compiler\AddProcessorsPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class MonologBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass($channelPass = new LoggerChannelPass());
        $container->addCompilerPass(new DebugHandlerPass($channelPass));
        $container->addCompilerPass(new AddProcessorsPass());
        $container->addCompilerPass(new AddSwiftMailerTransportPass());
    }
}
