<?php

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Unilend\DependencyInjection\Compiler\{MakeSonataCacheSymfonyPublicPass, OverrideNewRelicSettingsForConsolePass};

class AppKernel extends Kernel
{
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
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Unilend\Bundle\FrontBundle\UnilendFrontBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle,
            new Xynnn\GoogleTagManagerBundle\GoogleTagManagerBundle(),
            new Sonata\SeoBundle\SonataSeoBundle(),
            new Knp\Bundle\SnappyBundle\KnpSnappyBundle(),
            new Ekino\NewRelicBundle\EkinoNewRelicBundle(),
            new Sonata\CacheBundle\SonataCacheBundle(),
            new Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle(),
            new Unilend\Bundle\StoreBundle\UnilendStoreBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new EightPoints\Bundle\GuzzleBundle\EightPointsGuzzleBundle(),
            new Unilend\Bundle\WSClientBundle\UnilendWSClientBundle(),
            new RobertoTru\ToInlineStyleEmailBundle\RobertoTruToInlineStyleEmailBundle(),
            new Welp\MailchimpBundle\WelpMailchimpBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Http\HttplugBundle\HttplugBundle(),
            new Nexy\SlackBundle\NexySlackBundle(),
            new Cravler\MaxMindGeoIpBundle\CravlerMaxMindGeoIpBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
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

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideNewRelicSettingsForConsolePass());
        $container->addCompilerPass(new MakeSonataCacheSymfonyPublicPass());
    }
}
