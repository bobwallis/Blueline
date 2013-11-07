<?php
namespace Blueline\BluelineBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root( 'blueline' );
        $rootNode
                ->children()
                ->scalarNode( 'admin_email')->defaultValue( 'blueline@example.com' )->end()
                ->scalarNode( 'cache_manifest')->defaultFalse()->end()
                ->scalarNode( 'analytics_code')->defaultNull()->end()
                ->scalarNode( 'asset_update')->defaultValue( '0000-00-00 00:00:00+00:00' )->end()
                ->scalarNode( 'database_update')->defaultValue( '0000-00-00 00:00:00+00:00' )->end()
                ->end();

        return $treeBuilder;
    }
}
