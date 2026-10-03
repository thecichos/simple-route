<?php

namespace Route;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Route
{
	public function __construct(
		public string $method,
		public string $path,
	) {}
}