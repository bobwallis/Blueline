<?php
namespace Blueline\BluelineBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class BluelineExtension extends Extension
{
    public function load( array $configs, ContainerBuilder $container )
    {
        // Get configuration in the blueline namespace
        $configuration = $this->getConfiguration( $configs, $container );
        $config = $this->processConfiguration( $configuration, $configs );

        // Load configuration
        $loader = new Loader\YamlFileLoader( $container, new FileLocator( __DIR__.'/../Resources/config' ) );
        $loader->load( 'config.yml' );

        // Set blueline parameter
        $container->setParameter( 'blueline', $config );
    }
}
