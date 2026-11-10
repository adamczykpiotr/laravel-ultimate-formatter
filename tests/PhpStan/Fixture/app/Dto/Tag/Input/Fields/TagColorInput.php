<?php

declare(strict_types=1);

namespace App\Dto\Tag\Input\Fields;

use App\Dto\BaseInput;

class TagColorInput extends BaseInput
{
    public function __construct(
        public string $color,
    ) {}
}
