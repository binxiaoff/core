<?php
namespace Unilend\core;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    public function __construct($environment, $debug, $name = 'default')
    {
        $this->name = $name;
        parent::__construct($environment, $debug);
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the root path
     * @return string
     */
    public function getRootDir()
    {
        if (null === $this->rootDir) {
            $r             = new \ReflectionObject($this);
            $this->rootDir = realpath(dirname($r->getFileName()) . '/..');
        }

        return $this->rootDir;
    }

    public function getLogDir()
    {
        return $this->rootDir . '/log';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->rootDir . '/Config/config_' . $this->environment . '.yml');
    }

    /**
     * @return BundleInterface[]
     */
    public function registerBundles()
    {
        $bundles = [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Cache\AdapterBundle\CacheAdapterBundle(),
        ];

        return $bundles;
    }
}
