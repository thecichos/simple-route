<?php

namespace Controller;

use Route\Route;

class Controller
{
	protected string $folderPath;

	protected static function getFolderName(): string
	{

		$reflector = new \ReflectionClass(static::class);

		$path = $reflector->getFileName();

		$path = preg_replace('/\/[a-zA-Z0-9]*\.php$/', '', $path);
		
		return $path;
	}

	public static function getClassName(): string
	{
		return static::class;
	}

	public function __construct()
	{
		
		$this->folderPath = self::getFolderName();
	}

	/**
	 * @return string
	 */
	public function getFolderPath(): string
	{
		return $this->folderPath;
	}

	public function hasMethodWithPath(string $path): bool
	{
		$classReflection = new \ReflectionClass(static::class);

		foreach ($classReflection->getMethods() as $reflectionMethod)
		{

			$attributes = $reflectionMethod->getAttributes("Route\Route");

			if (count($attributes) === 0)
			{
				continue;
			}

			/** @var Route $Route */
			$Route = $attributes[0]->newInstance();

			if ($Route->path === $path)
			{
				return true;
			}
		}

		return false;
	}

	public function getActualClassName(): string
	{

		$lsFullyQualifiedClassParticles = $this->getClassName();
		$lsFullyQualifiedClassParticles = explode("\\", $lsFullyQualifiedClassParticles);
		$actualClassName = array_reverse($lsFullyQualifiedClassParticles)[0];

		return $actualClassName;
	}
}