<?php

namespace Discovery;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use SplFileInfo;
use Route\Route;

final class Discovery
{
	private array $routes = [];

	public function __construct(
		private readonly string $basePath,
		private $callableUriModification
	) {}

	/**
	 * @return array<int, array{
	 *     class: string,
	 *     method: string,
	 *     httpMethod: string,
	 *     path: string,
	 *     group: string
	 * }>
	 */
	public function discover() : void
	{
		$routes = [];

		foreach ($this->discoverFiles() as $filePath) {
			foreach ($this->discoverClassesFromFile($filePath) as $className) {
				$routes = array_merge($routes, $this->readRoutesFromClass($className));
			}
		}

		$this->routes = $routes;
	}

	public function call() : void
	{

		if (empty($this->routes)) {
			throw new RuntimeException('No routes found');
		}

		$routeFound = false;
		$class = null;
		$method = null;

		$URI = $_SERVER['REQUEST_URI'];

		if ($this->callableUriModification !== null) {
			$URI = ($this->callableUriModification)($URI);
		}

		if (empty($URI)) {
			$URI = "/";
		}

		$METHOD = $_SERVER['REQUEST_METHOD'];
		
		foreach ($this->routes as $route) {

			if (
				$URI === $route['path']
				&& $METHOD === $route['httpMethod']
			) {

				$class = $route['class'];
				$method = $route['method'];

				$routeFound = true;
			}
		}

		if ($routeFound) {
			$class::{$method}();
		} else {
			throw new RuntimeException('Route not found');
		}
	}

	/**
	 * @return array<int, string>
	 */
	private function discoverFiles() : array
	{
		$directory = rtrim($this->basePath, '/\\');

		if (!is_dir($directory)) {
			throw new RuntimeException('Route base path does not exist: ' . $directory);
		}

		$files = [];

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
		);

		foreach ($iterator as $fileInfo) {
			
			if (!$fileInfo instanceof SplFileInfo) {
				continue;
			}

			if ($fileInfo->getExtension() !== 'php') {
				continue;
			}

			$files[] = $fileInfo->getRealPath() ?: $fileInfo->getPathname();
		}

		return $files;
	}

	/**
	 * @return array<int, string>
	 * @throws \ReflectionException
	 */
	private function discoverClassesFromFile(string $filePath) : array
	{
		$before = get_declared_classes();

		require_once $filePath;

		$after = get_declared_classes();
		$newClasses = array_diff($after, $before);
		$found = [];

		foreach ($newClasses as $className) {
			
			$reflectionClass = new ReflectionClass($className);

			if ($reflectionClass->isAbstract() || $reflectionClass->isInterface() || $reflectionClass->isTrait()) {
				continue;
			}

			if (realpath($reflectionClass->getFileName() ?: '') !== realpath($filePath)) {
				continue;
			}

			$found[] = $className;
		}

		return $found;
	}

	/**
	 * @return array<int, array{
	 *     class: string,
	 *     method: string,
	 *     httpMethod: string,
	 *     path: string,
	 *     group: string
	 * }>
	 */
	private function readRoutesFromClass(string $className) : array
	{
		$reflectionClass = new ReflectionClass($className);
		$routes = [];

		if ($reflectionClass->isAbstract() || $reflectionClass->isInterface() || $reflectionClass->isTrait()) {
			return [];
		}

		foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
			foreach ($reflectionMethod->getAttributes(Route::class) as $attribute) {


				/** @var Route $route */
				$route = $attribute->newInstance();

				$routes[] = [
					'class' => $reflectionClass->getName(),
					'method' => $reflectionMethod->getName(),
					'httpMethod' => strtoupper($route->method),
					'path' => $route->path
				];
			}
		}

		return $routes;
	}
}