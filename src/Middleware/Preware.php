<?php

namespace Middleware;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Preware
{

	public function __construct(
		public string $class,
		public string $method,
		public ?array $args = null
	)
	{

	}

}