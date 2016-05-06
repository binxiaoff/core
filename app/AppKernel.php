<?php

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
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
            new \Unilend\Bundle\KernelBundle\KernelBundle(),
        ];

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->rootDir . '/config/config_' . $this->environment . '.yml');
    }
}
