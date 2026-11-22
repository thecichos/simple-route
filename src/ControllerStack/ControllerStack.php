<?php

namespace ControllerStack;

use Controller\Controller;
use Exception;
use Middleware\Postware;
use Middleware\Preware;
use ReflectionClass;
use ReflectionMethod;
use Route\Route;

class ControllerStack
{

	private static bool $sanityChecked = false;

	private Controller $rootController;
	/** @var ControllerStack[] $childControllerStacks  */
	private array $childControllerStacks = [];
	/** @var ReflectionMethod[] $methods  */
	private array $methods = [];

	/**
	 * This method is used to ensure that a root controller does not have a method with the same name as a child controller.
	 * @return void
	 */
	private function sanityCheck() : void
	{

		$lsControllerNames = [];

		foreach ($this->childControllerStacks as $childControllerStack)
		{

			$lsControllerNames[] = $childControllerStack->rootController->getActualClassName();
		}

		foreach ($lsControllerNames as $controllerName)
		{
			
			if ($this->rootController->hasMethodWithPath("/".$controllerName))
			{
				throw new Exception("Root controller has a method with the same name as a child controller");
			}
		}

		// Check child controllers with sanitychecks
		foreach ($this->childControllerStacks as $childControllerStack)
		{
			$childControllerStack->sanityCheck();
		}

	}
	
	public function setRootController(Controller $controller)
	{
		
		$reflectionRootController = new ReflectionClass($controller);
		$hasRootRoute = false;
		
		foreach ($reflectionRootController->getMethods() as $reflectionMethod)
		{

			$attributes = $reflectionMethod->getAttributes("Route\Route");
			
			if (count($attributes) === 0)
			{
				continue;
			}

			/** @var Route $Route */
			$Route = $attributes[0]->newInstance();

			if ($Route->path === "/")
			{
				$hasRootRoute = true;
			}
			$this->methods[] = $reflectionMethod;
		}

		if (!$hasRootRoute)
		{
			throw new Exception("Root controller must have a route with path '/'");
		}

		$this->rootController = $controller;

		/** @var Controller $ins */
		$ins = $reflectionRootController->newInstance([]);
		
		$folders = glob($ins->getFolderPath()."/*");
		
		foreach ($folders as $folder)
		{
			if (preg_match("/.php$/", $folder))
			{
				continue;
			}

			$files = glob($folder."/*");

			foreach ($files as $file)
			{

				if (!preg_match("/.php$/", $file)) continue;

				$filenameFromNamespace = preg_replace('/\\\/', '/', $reflectionRootController->getNamespaceName());

				$lsFile = explode($filenameFromNamespace, $file);

				$filename = $lsFile[1];

				$fullyQualifiedFilename = $reflectionRootController->getNamespaceName().$filename;

				$fullyQualifiedFilename = preg_replace('/.php$/', '', $fullyQualifiedFilename);

				$fullyQualifiedClassName = preg_replace('/\//', '\\', $fullyQualifiedFilename);
				
				$reflectionClass = new ReflectionClass($fullyQualifiedClassName);

				$parentClass = $reflectionClass->getParentClass();

				if ($parentClass === false) continue;

				if ($parentClass->getName() === "Controller\Controller")
				{

					/** @var Controller $classInstance */
					$classInstance = $reflectionClass->newInstance([]);

					$cs = new ControllerStack();
					$cs->setRootController($classInstance);

					$this->childControllerStacks[] = $cs;
				}
			}
		}
	}

	public function run(): bool
	{

		if (!self::$sanityChecked) {
			$this->sanityCheck();
			self::$sanityChecked = true;
		}

		$URI = $_SERVER['REQUEST_URI'];

		if (empty($URI)) {
			$URI = "/";
		}

		$METHOD = $_SERVER['REQUEST_METHOD'];

		foreach ($this->methods as $method)
		{
			$refroute = $method->getAttributes("Route\Route")[0];

			/** @var Route $route */
			$route = $refroute->newInstance();

			if ($route->method !== $METHOD) {
				continue;
			}

			$path = preg_replace("/\/\{([a-zA-Z0-9]*})}/", "/$1", $route->path);

			$actualPath = "/".$this->rootController->getActualClassName().$path;
			$actualPathAlt = $actualPath."/";

			$args = [];

			$incomingPath = explode("/", $URI);
			$boolNoMatch = false;
			
			foreach (explode("/", $actualPathAlt) as $index => $pathPart)
			{

				if (empty($incomingPath[$index])) {
					continue;
				}

				if (str_starts_with($pathPart, "{")) {
					$args[] = $incomingPath[$index];
				} else if ($pathPart !== $incomingPath[$index]) {
					$boolNoMatch = true;
					break;
				}
			}

			if ($boolNoMatch) {
				continue;
			}



			if ($lsPreWare = $method->getAttributes("Middleware\Preware")) {
				foreach ($lsPreWare as $refPreWare) {

					/** @var Preware $preWare */
					$preWare = $refPreWare->newInstance();
					$preWare->class::{$preWare->method}(...$args);
				}
			}

			$method->invoke(null, ...$args);

			if ($lsPostWare = $method->getAttributes("Middleware\Postware")) {
				foreach ($lsPostWare as $refPostWare) {

					/** @var Postware $postWare */
					$postWare = $refPostWare->newInstance();
					$postWare->class::{$postWare->method}(...$args);
				}
			}
			return true;
		}

		foreach ($this->childControllerStacks as $childControllerStack) {

			try {
				return $childControllerStack->run();
			} catch (\Exception $e) {
				continue;
			}
		}

		throw new \Exception("No route found for $METHOD $URI");
	}
}