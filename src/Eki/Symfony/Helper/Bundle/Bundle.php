<?php

namespace Eki\Symfony\Helper\Bundle;

use Eki\Symfony\Helper\Bundle\RegisterBundlesInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle as BaseBundle;

class Bundle extends BaseBundle implements RegisterBundlesInterface
{
	/**
	* Register bundles
	* 
	* @param array Exclude bundles
	* @params Kernel $kernel
	* 
	* @return array Array of bundles
	*/
	public function registerBundles($excludeBundles, KernelInterface $kernel = null)
	{
		$bundles = array();
		
		$env = $kernel !== null ? $kernel->getEnvironment() : null;
		
		$excludeClasses = array();
		foreach($excludeBundles as $bundle)
		{
			$excludeClasses[] = get_class($bundle);
		}
		foreach($this->getDependencyBundleList($env) as $bundleClass)
		{
			if (!in_array($bundleClass, $excludeClasses))
			{
				$excludeBundles[] = $bundles[] = $bdl = new $bundleClass();
				if (method_exists($bdl,'registerBundles'))
				{
					$subBdls = $bdl->registerBundles($excludeBundles, $kernel);
					foreach($subBdls as $subBdl)
					{
						$excludeBundles[] = $bundles[] = $subBdl;
					}
				}
			}
		}
		
		return $bundles;
	}

	/**
	 * Determines bundles to register
	 * 
	 * @param string $environment
	 * 
	 * @return array Array of bundles  
	*/	
	protected function getDependencyBundleList( $environment = null )
	{
		return array(
			// '<bundlesName>' => '<bundle class>',
		);
	}
}
