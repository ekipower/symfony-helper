<?php

namespace Eki\Symfony\Helper\Extension;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Parser;
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
     * @param string $bundleName
     * @param string $alias
     * @param bool $addResource
     * @param bool $aliasIncluded
     *
     * @return void
     */
	static public function configureBundle(
		Extension $forExtension, 
		ContainerBuilder $container, 
		$bundleName, 
		$alias, 
		$addResource = false,
		$aliasIncluded = false
	)
	{
		$bundles = $container->getParameter('kernel.bundles');
		if (!isset($bundles[$bundleName.'Bundle']))
			return;

        foreach ($container->getExtensions() as $name => $extension) 
		{
			if ($name === $alias)
			{
				$configs = array();
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
	                //$container->prependExtensionConfig( $name, $config );
	                $container->prependExtensionConfig( $name, $aliasIncluded ? $config[$alias] : $config );
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
		//$extensionReflector = new ReflectionClass(get_class($extension));
		//$configDir = dirname($extensionReflector->getFileName()) . '/../Resources/config';
		$configDir = self::configDir($extension);
		$file = null;

		if (file_exists( $configDir . "//" . $alias . ".yml" ))
		{
			$file = $configDir . "//" . $alias . ".yml";
			$configs = array( Yaml::parse( file_get_contents( $file ) ) );
		}
		else if ( file_exists( $configDir . "//" . $alias ) && is_dir( $configDir . "//" . $alias ) )
		{
			if ( file_exists( $configDir . "//" . $alias . "//filelist.yml" ) )
			{
				$order = Yaml::parse( file_get_contents( $configDir . '/' . $alias . '/filelist.yml' ) );
				foreach ($order['list'] as $filename) 
				{
					$configs[] = Yaml::parse( file_get_contents( $configDir . "//" . $alias . "//" . $filename ) ); 
				}
			}
			else
			{
				$finder = new Finder();
				$finder->sortByName()->files()->in( $configDir . "//" . $alias );
				foreach ($finder as $file) 
				{
//					$configs[] = Yaml::parse( file_get_contents( $file->getPathname() ) ); 
					$configs[] = Yaml::parse( $file->getContents() ); 
				}
			}
		}
		else
		{
			throw new \LogicException('Cannot find config file.' );
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
	static public function configureGroupBundles(Extension $extension, ContainerBuilder $container, $groupName)
	{
		//$extensionReflector = new ReflectionClass(get_class($extension));

        //$configs =  Yaml::parse( file_get_contents( 
			//dirname($extensionReflector->getFileName()) . '/../Resources/config/' . $groupName . '.yml' ) );

		$configDir = self::configDir($extension);
        $configs =  Yaml::parse( file_get_contents( $configDir . "//" . $groupName . '.yml' ) );
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

/////////////////////////////

	static private function getConfigFromFile(Extension $extension, $alias, $filename = null)
	{
		$configDir = self::configDir($extension);
		$filename = self::configDir($extension) . "//" . ( $filename === null ? ($alias.'.yml') : $filename );	

	    $yaml = new Parser();
		$resourceFile = null;

		if ( file_exists( $filename ) )
		{
			$configs = $yaml->parse( file_get_contents( $filename ) );
			$resourceFile = $filename;
		}
		else if ( file_exists( $configDir . "//" . $alias ) && is_dir( $configDir . "//" . $alias ) )
		{
			$configs = array();
			if ( file_exists( $configDir . "//" . $alias . "//filelist.yml" ) )
			{
				$order = $yaml->parse( file_get_contents( $configDir . "//" . $alias . "//filelist.yml" ) );
				foreach ($order['list'] as $fn) 
				{
					$configs = array_merge(
						$configs,
						$yaml->parse( file_get_contents( $configDir . "//" . $alias . "//" . $fn ) )
					); 
				}
			}
			else
			{
				$finder = new Finder();
				$finder->sortByName()->files()->in( $configDir . "//" . $alias );
				foreach ($finder as $file) 
				{
					$configs = array_merge(
						$configs,
						$yaml->parse( $file->getContents() )
					); 
				}
			}
		}
		else
		{
			throw new \LogicException("Cannot find config file " . $filename . " for extension " . $alias . "." );
		}

		return array(
			'config' => $configs,
			'resource_file' => $resourceFile,
		);
	}

	static private function configDir(Extension $extension)
	{
		$extensionReflector = new ReflectionClass(get_class($extension));
		return dirname($extensionReflector->getFileName()) . "//..//Resources//config";
	}

	/**
	* Prepend for given extension 
	* 
	* @param Extension $extension
	* @param ContainerBuilder $container
	* @param string $alias
	* @param string|null $filename
	* @param bool $aliasIncluded
	* 
	* @return void
	* @throw
	*/	
	static public function prependExtension(
		Extension $extension, 
		ContainerBuilder $container, 
		$alias, 
		$filename = null, 
		$aliasIncluded = false)
	{
		if ($container->hasExtension($alias)) 
		{
            $configArray = self::getConfigFromFile($extension, $alias, $filename);
            $config = $configArray['config'];
            $container->prependExtensionConfig($alias, !$aliasIncluded ? $config : $config[$alias]);
            if ($configArray['resource_file'] !== null)
            	$container->addResource(new FileResource($configArray['resource_file']));
        }
	}
}
