<?php

namespace ControllerStack;

use Controller\Controller;
use Controller\ControllerPrefix;
use Exception;
use Middleware\Postware;
use Middleware\Preware;
use ReflectionClass;
use ReflectionMethod;
use Route\Route;
use Throwable;


class ControllerStack
{

	private static bool $sanityChecked = false;

	private Controller $rootController;
	/** @var ControllerStack[] $childControllerStacks  */
	private array $childControllerStacks = [];
	
	/** @var Controller[] $namedControllers  */
	private array $namedControllers = [];

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

		foreach ($this->namedControllers as $controller)
		{

			$actualClassName = $controller->getActualClassName();

			if (array_search($actualClassName, $lsControllerNames) !== false)
			{
				throw new Exception("Named controller has the same name as a child controller stack");
			}

			$lsControllerNames[] = $actualClassName;
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

	public function addNamedController(string $name, Controller $controller): void
	{

		$refController = new ReflectionClass($controller);

		$refNamedControllerAttr = $refController->getAttributes("Controller\ControllerPrefix");
		
		if (count($refNamedControllerAttr) === 0) throw new Exception("Controller must have NamedController attribute");

		$this->namedControllers[$name] = $controller;
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

				/* @Todo Make it so that the arguments can be typed using {<name>:<type>} Where type can be a regex for slug */
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



			self::executeMethod($method, $args);
			return true;
		}

		foreach ($this->childControllerStacks as $childControllerStack) {

			try {
				return $childControllerStack->run();
			} catch (\Exception $e) {
				continue;
			}
		}
		


		foreach($this->namedControllers as $controller)
		{
			$refController = new \ReflectionClass($controller);

			/** @var ControllerPrefix $prefix */
			$ControllerPrefix = $refController->getAttributes("Controller\ControllerPrefix")[0];

			$insPrefix = $ControllerPrefix->newInstance();

			$prefix = $insPrefix->prefix;

			$prefix = str_replace("/", "\/", $prefix);

			if (preg_match("/^{$prefix}/", $URI)) {


				$prefixLessUri = preg_replace("/^{$prefix}/", "", $URI);
				$prefixLessUriAlt = $prefixLessUri."/";
				foreach($refController->getMethods() as $method)
				{

					$refRoute = $method->getAttributes("Route\Route");

					if ($refRoute)
					{
						
						foreach($refRoute as $route)
						{
							/** @var Route $insRoute */
							$insRoute = $route->newInstance();

							$match = true;
							$args = [];

							$incomingPath = explode("/", $prefixLessUriAlt);
							$path = explode("/", $insRoute->path);

							foreach($path as $index => $value)
							{
								if (str_starts_with($value, "{")) {
									$args[] = $incomingPath[$index];
								} else if ($value !== $incomingPath[$index]) {
									$match = false;
									break;
								}
							}

							if ($match) {
								if ($METHOD === $insRoute->method) {

									/** @TODO This is not pretty, make it prettier */
									try {
										self::executeMethod($method, $args);
										return true;
									} catch (Throwable $th) {
										continue;
									}
								}
							}
						}
					}
				}
			}
		}

		throw new \Exception("No route found for $METHOD $URI");
	}

	private static function executeMethod(
		ReflectionMethod $METHOD,
		array $args,
		?Controller $controller = null
	): bool
	{


		if ($lsPreWare = $METHOD->getAttributes("Middleware\Preware")) {
			foreach ($lsPreWare as $refPreWare) {

				/** @var Preware $preWare */
				$preWare = $refPreWare->newInstance();
				$preWare->class::{$preWare->method}(...$args);
			}
		}

		$METHOD->invoke(null, ...$args);

		if ($lsPostWare = $METHOD->getAttributes("Middleware\Postware")) {
			foreach ($lsPostWare as $refPostWare) {

				/** @var Postware $postWare */
				$postWare = $refPostWare->newInstance();
				$postWare->class::{$postWare->method}(...$args);
			}
		}
		
		return true;
	}
}