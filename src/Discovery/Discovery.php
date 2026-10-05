<?php

namespace Discovery;

use Middleware\Middleware;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use SplFileInfo;
use Route\Route;

final class Discovery
{
	private array $routes = [];

	/**
	 * @param array<int, string> $foldersToSearch An array of folder paths to search.
	 * @param callable $callableUriModification A callable to handle URI modifications.
	 * @return void
	 */
	public function __construct(
		private readonly array $foldersToSearch,
		private                $callableUriModification
	) {}

	/**
	 * Discovers and processes routes from files and classes, storing the resulting routes internally.
	 *
	 * @return void
	 * @throws ReflectionException
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

	/**
	 * Matches the current request URI and HTTP method against the registered routes,
	 * and invokes the corresponding class method if a match is found.
	 *
	 * @return void
	 *
	 * @throws RuntimeException If no routes are registered or no matching route is found.
	 */
	public function call() : void
	{

		if (empty($this->routes)) {
			throw new RuntimeException('No routes found');
		}

		$routeFound = false;
		$class = null;
		$method = null;

		$middleware = null;

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

				$middleware = $route['middleware'];

				$routeFound = true;
			}
		}

		if ($routeFound) {
			
			if (!empty($middleware)) {

				foreach ($middleware["before"] as $mw) {
					if ($mw["args"]) {
						$mw["class"]::{$mw["method"]}(...$mw["args"]);
					} else {
						$mw["class"]::{$mw["method"]}();
					}
				}
			}

			$class::{$method}();

			if (!empty($middleware)) {
				foreach ($middleware["after"] as $mw) {
					if ($mw["args"]) {
						$mw["class"]::{$mw["method"]}(...$mw["args"]);
					} else {
						$mw["class"]::{$mw["method"]}();
					}
				}
			}
		} else {
			throw new RuntimeException('Route not found');
		}
	}

	/**
	 * Discovers and retrieves a list of PHP files from the specified folders.
	 *
	 * The method iterates through the configured directories, validating their
	 * existence, and searches for PHP files using a recursive directory iterator.
	 * Only files with the `.php` extension are included in the returned list.
	 *
	 * @return array An array of file paths pointing to the discovered PHP files.
	 *
	 * @throws RuntimeException If a specified base path does not exist.
	 */
	private function discoverFiles() : array
	{

		$files = [];

		foreach($this->foldersToSearch as $basePath) {
			$directory = rtrim($basePath, '/\\');

			if (!is_dir($directory)) {
				throw new RuntimeException('Route base path does not exist: ' . $directory);
			}

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


		}
		return $files;

	}

	/**
	 * Discovers and retrieves a list of non-abstract classes defined in a specified PHP file.
	 *
	 * This method evaluates the file to detect class declarations and filters out abstract
	 * classes, interfaces, and traits. It ensures that only classes directly declared
	 * in the provided file are included.
	 *
	 * @param string $filePath The path to the PHP file to be analyzed for class declarations.
	 *
	 * @return array An array of fully qualified class names discovered in the file.
	 * @throws ReflectionException
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
	 * Reads and retrieves route definitions from attributes in a given class.
	 *
	 * This method uses reflection to inspect a specified class, retrieves public
	 * methods annotated with `Route` attributes, and extracts relevant information
	 * such as HTTP methods, paths, and related class/method metadata.
	 * Abstract classes, interfaces, and traits are skipped during processing.
	 *
	 * @param string $className The fully qualified name of the class to inspect for route definitions.
	 *
	 * @return array An array of associative arrays representing route definitions. Each route definition includes:
	 *               - 'class': The name of the class defining the route.
	 *               - 'method': The name of the method annotated with a route.
	 *               - 'httpMethod': The HTTP method specified in the route (e.g., GET, POST).
	 *               - 'path': The path specified for the route.
	 * @throws ReflectionException
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

				$middlewareAttributes = [];

				foreach ($reflectionMethod->getAttributes(Middleware::class) as $middlewareAttribute) {

					$middleware = $middlewareAttribute->newInstance();
					$tempMiddleware = [];
					$tempMiddleware["class"] = $middleware->class;
					$tempMiddleware["method"] = $middleware->method;
					$tempMiddleware["args"] = $middleware->args;
					$tempMiddleware["when"] = $middleware->when;

					$middlewareAttributes[$middleware->when][] = $tempMiddleware;
				}

				$routes[] = [
					'class' => $reflectionClass->getName(),
					'method' => $reflectionMethod->getName(),
					'httpMethod' => strtoupper($route->method),
					'path' => $route->path,
					'middleware' => $middlewareAttributes
				];
			}
		}

		return $routes;
	}
}