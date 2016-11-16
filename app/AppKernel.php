<?php
use Monolog\ErrorHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AppKernel extends Kernel
{
    /**
     * Temporary error log solution before the migration 100% to the Symfony Framework
     */
    public function boot()
    {
        if (true === $this->booted) {
            return;
        }
        parent::boot();
        $logger = $this->getContainer()->get('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE);
        if ($logger instanceof LoggerInterface) {
            ErrorHandler::register($logger, [], LogLevel::ERROR, LogLevel::ERROR);
        }
    }

    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Cache\AdapterBundle\CacheAdapterBundle(),
            new Unilend\Bundle\CoreBusinessBundle\UnilendCoreBusinessBundle(),
            new Unilend\Bundle\CommandBundle\UnilendCommandBundle(),
            new Unilend\Bundle\MessagingBundle\UnilendMessagingBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Unilend\Bundle\FrontBundle\UnilendFrontBundle(),
            new Unilend\Bundle\TranslationBundle\UnilendTranslationBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle,
            new Xynnn\GoogleTagManagerBundle\GoogleTagManagerBundle(),
            new Sonata\SeoBundle\SonataSeoBundle(),
            new Ekino\Bundle\NewRelicBundle\EkinoNewRelicBundle(),
            new Sonata\CacheBundle\SonataCacheBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
        }
        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__) . '/var/cache/' . $this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__) . '/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->rootDir . '/config/config_' . $this->environment . '.yml');
    }
}
