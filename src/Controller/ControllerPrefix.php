<?php

namespace Controller;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ControllerPrefix
{
	public function __construct(
		public string $prefix
	){}
}