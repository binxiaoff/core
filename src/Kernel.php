<?php

declare(strict_types=1);

namespace KLS;

use KLS\Core\DataTransformer\FileInputDataUploadInterface;
use KLS\Core\EventSubscriber\Jwt\PermissionProviderInterface;
use KLS\Core\Security\Voter\FileDownloadVoterInterface;
use KLS\Core\MessageHandler\File\FileUploadedNotifierInterface;
use KLS\Core\Service\File\FileDeleteInterface;
use KLS\Core\Service\Staff\StaffLoginInterface;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const DOMAINS = '{core,syndication,credit_guaranty}';

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/{packages}/*.yaml');
        $container->import('../config/{packages}/' . $this->environment . '/*.yaml');
        $container->import('../config/{services}/' . self::DOMAINS . '/services.yaml');
        $container->import('../config/{services}/' . self::DOMAINS . '/services_' . $this->environment . '.yaml');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/{routes}.yaml');
        $routes->import('../config/{routes}/*.yaml');
        $routes->import('../config/{routes}/' . $this->environment . '/*.yaml');
    }

    /**
     * @see https://symfony.com/doc/current/service_container/tags.html#autoconfiguring-tags
     *
     * @todo To see if possible to have a common.yaml file and importing or declaring it (I tried but it did not work..)
     */
    protected function build(ContainerBuilder $container): void
    {
        // Authentication
        $container->registerForAutoconfiguration(PermissionProviderInterface::class)->addTag('kls.jwt.permission_provider');
        $container->registerForAutoconfiguration(StaffLoginInterface::class)->addTag('kls.staff.login.checker');

        // File system
        $container->registerForAutoconfiguration(FileInputDataUploadInterface::class)->addTag('kls.file.input.data.upload');
        $container->registerForAutoconfiguration(FileDownloadVoterInterface::class)->addTag('kls.file.download.voter');
        $container->registerForAutoconfiguration(FileUploadedNotifierInterface::class)->addTag('kls.file.uploaded.notifier');
        $container->registerForAutoconfiguration(FileDeleteInterface::class)->addTag('kls.file.delete');
    }
}
