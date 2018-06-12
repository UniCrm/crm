<?php

namespace UniCrm\Bundles\CalendarBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 */
class UniCrmCalendarExtension extends Extension
{

    /**
     * @param array $configs specified in config.yml
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');


        // MAKE THE CONFIG DEFINITIONS AVAILABLE ON CONTAINER
        foreach ($configs as  $config){
            if (sizeof($config)){
                foreach ($config as $provider => $providerConfig){
                    if (sizeof($providerConfig)){
                        foreach ($providerConfig as $providerConfigKey => $providerConfigValue){
                            $containerId = 'calendar'.'.'.$provider.'.'.$providerConfigKey;
                            if (strlen($containerId)){
                                $container->setParameter($containerId,$providerConfigValue);
                            }
                        }
                    }
                }
            }
        }




    }
}
