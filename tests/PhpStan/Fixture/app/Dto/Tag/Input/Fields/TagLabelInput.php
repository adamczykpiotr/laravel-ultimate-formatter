<?php

declare(strict_types=1);

namespace App\Dto\Tag\Input\Fields;

class TagLabelInput extends BaseFieldInput
{
    public function __construct(
        public string $label,
    ) {}
}
