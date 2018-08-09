<?php

namespace Unilend\Bundle\FrontBundle\DependencyInjection;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class UnilendFrontExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('form.xml');
        $loader->load('event.xml');

        $this->updateValidatorMappingFiles($container);
    }

    /**
     * Gets the validation mapping files for the format
     *
     * @param ContainerBuilder $container
     */
    private function updateValidatorMappingFiles(ContainerBuilder $container)
    {
        $files = [];

        if ($container->hasParameter('validator.mapping.loader.yaml_files_loader.mapping_files')) {
            $files = $container->getParameter('validator.mapping.loader.yaml_files_loader.mapping_files');
        }

        $validationPath = __DIR__ . '/../Resources/config/validation/';

        $validatorFiles = array_diff(scandir($validationPath), ['.', '..']);

        foreach ($validatorFiles as $file) {
            if (false === is_dir($file) && 1 === preg_match('#(.+)\.yml#', $file, $matches)) {
                $files[] = realpath($validationPath . $file);
                $container->addResource(new FileResource($validationPath . $file));
            }
        }

        $container->setParameter('validator.mapping.loader.yaml_files_loader.mapping_files', $files);
    }
}
