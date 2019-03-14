<?php

namespace NelmioApiDocGenerator\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class NelmioApiDocGeneratorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
		$loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!isset($config['functions'])) {
            return;
        }

        $nelmioApiDocGeneratorDefinition = $container->getDefinition('NelmioApiDocGenerator\Services\NelmioApiDocGenerator');
        if (isset($config['functions']['serialization_groups'])) {
            $nelmioApiDocGeneratorDefinition->setArgument('$fGroups', $config['functions']['serialization_groups']);
        }
        if (isset($config['functions']['http_responses'])) {
            $nelmioApiDocGeneratorDefinition->setArgument('$fHttpResponses', $config['functions']['http_responses']);
        }
        if (isset($config['functions']['return'])) {
            $nelmioApiDocGeneratorDefinition->setArgument('$fdataReturnedCollector', $config['functions']['return']);
        }
    }
}
