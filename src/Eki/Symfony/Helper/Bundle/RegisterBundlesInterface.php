<?php

namespace Eki\Symfony\Helper\Bundle;

use Symfony\Component\HttpKernel\KernelInterface;

interface RegisterBundlesInterface
{
	/**
	* Register bundles
	* Called from kernel 
	*
	* @params array Excluded bundles 
	* @param Kernel $kernel
	* 
	* @return array Registered bundles
	*/
	public function registerBundles($excludeBundles, KernelInterface $kernel = null);
}
