<?php

declare(strict_types=1);

namespace App\Services;

final readonly class TagMarkerService
{
    public function name(string $name): string
    {
        $first = sprintf('%s', $name); // formatter-ignore ultimateFormatter.disallowSprintf
        // formatter-ignore ultimateFormatter.disallowSprintf, ultimateFormatter.disallowNativeArrayFunctions -- legacy
        $second = sprintf('%s', implode(',', array_map(fn(string $tag): string => $tag, [$first])));

        return sprintf('%s', $second); // formatter-ignore ultimateFormatter.disallowFileExists
    }
}
