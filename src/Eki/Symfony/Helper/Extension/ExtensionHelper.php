<?php

namespace Eki\Symfony\Helper\Extension;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Config\Resource\FileResource;

use \ReflectionClass;

class ExtensionHelper
{
    /**
     * Configures a bundle with name and alias.
	 * 
     * @param Extension $extension Extension
     * @param ContainerBuilder $container The service container
     *
     * @return void
     */
	static public function configureBundle(Extension $forExtension, ContainerBuilder $container, $bundleName, $alias, $addResource = false)
	{
		$bundles = $container->getParameter('kernel.bundles');
		if (!isset($bundles[$bundleName.'Bundle']))
			return;

        foreach ($container->getExtensions() as $name => $extension) 
		{
			if ($name === $alias)
			{
				if (method_exists($extension, 'build' . $bundleName . 'Config'))
				{
					$configs = array($this->$method($container));
					$file = null;
				}
				else
				{
					$ret = self::defaultGetConfigsFromFiles($forExtension, $alias);
					$configs = $ret['config'];
					$file = $ret['file'];
				}
				
				foreach($configs as $config)
				{
	                $container->prependExtensionConfig( $name, $config );
					if ($addResource && $file !== null)
					{
						$container->addResource(new FileResource($file));
					}
				}						  
                break;
            }
        }
	}

	static private function defaultGetConfigsFromFiles(Extension $extension, $alias)
	{
		$extensionReflector = new ReflectionClass(get_class($extension));
		$configDir = dirname($extensionReflector->getFileName()) . '/../Resources/config';
		$file = null;

		if (file_exists( $configDir . '/' . $alias . '.yml' ))
		{
			$file = $configDir . '/' . $alias . '.yml';
			$configs = array( Yaml::parse( file_get_contents( $file ) ) );
		}
		else if ( file_exists( $configDir . '/' . $alias ) && is_dir( $configDir . '/' . $alias ) )
		{
			if ( file_exists( $configDir . '/' . $alias . '/filelist.yml' ) )
			{
				$order = Yaml::parse( file_get_contents( $configDir . '/' . $alias . '/filelist.yml' ) );
				foreach ($order['list'] as $filename) 
				{
					$configs[] = Yaml::parse( file_get_contents( $configDir . '/' . $alias . '/' . $filename ) ); 
				}
			}
			else
			{
				$finder = new Finder();
				$finder->sortByName()->files()->in( $configDir . '/' . $alias );
				foreach ($finder as $file) 
				{
//					$configs[] = Yaml::parse( file_get_contents( $file->getPathname() ) ); 
					$configs[] = Yaml::parse( $file->getContents() ); 
				}
			}
		}

		return array(
			'config' => $configs,
			'file' => $file
		); 
	}
	
    /**
     * Configures a group of bundles
     *
     * @param Extension $extension Extension
     * @param ContainerBuilder $container The service container
     *
     * @return void
     */
	static public function configureGroupBundles(Exension $extension, ContainerBuilder $container, $groupName)
	{
		$extensionReflector = new ReflectionClass(get_class($extension));

        $configs =  Yaml::parse( file_get_contents( 
			dirname($extensionReflector->getFileName()) . '/../Resources/config/' . $groupName . '.yml' ) );
        foreach ($container->getExtensions() as $name => $extension) 
		{
			if (in_array($name, array_keys($configs)))
			{
				if (is_array($configs[$name]) && !empty($configs[$name]))
				{
	                $container->prependExtensionConfig( $name, $configs[$name] );
				}
			}
        }
	}
}
