<?php

namespace Route;

use Attribute;

/* @TODO make a few Route helpers (GET, POST, PUT, DELETE) where they just reference the Route class */

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Route
{
	public function __construct(
		public string $method,
		public string $path,
	) {}
}