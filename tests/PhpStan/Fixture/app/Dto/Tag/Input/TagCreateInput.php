<?php

declare(strict_types=1);

namespace App\Dto\Tag\Input;

use App\Dto\BaseInput;

class TagCreateInput extends BaseInput
{
    public function __construct(
        public string $name,
    ) {}
}
