<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Ignore;

use Illuminate\Support\Str;

/**
 * The `// formatter-ignore <identifier>[, <identifier>...]` markers of one file. A marker at the end of a line ignores
 * that line; a marker alone on its line ignores the next line of code. An identifier names a PHP CS Fixer rule
 * (`numeric_literal_separator`, `UltimateFormatter/numeric_literal_separator`), a Rector rule by its short class name
 * (`ChangeSwitchToMatchRector`) or a PHPStan error identifier (`ultimateFormatter.disallowSprintf`).
 */
class IgnoreMarkers
{
    public const MARKER = 'formatter-ignore';

    /**
     * @param array<int, list<string>> $targets Line => identifiers ignored on it.
     * @param array<int, list<string>> $markerLines Line of a marker alone on its line => identifiers it names.
     * @param array<int, string> $errors Line => why its marker is malformed.
     */
    public function __construct(
        public readonly array $targets = [],
        public readonly array $markerLines = [],
        public readonly array $errors = [],
    ) {}

    /**
     * @param string $code
     *
     * @return self
     */
    public static function fromCode(string $code): self
    {
        if (str_contains($code, self::MARKER) === false) {
            return new self();
        }

        $targets = [];
        $markerLines = [];
        $errors = [];
        $pending = [];
        $line = 1;
        $lastCodeLine = 0;

        // Without TOKEN_PARSE, so a file that does not parse yet still yields its markers.
        foreach (token_get_all($code) as $token) {
            [$kind, $text] = is_array($token) ? [$token[0], $token[1]] : [null, $token];
            $identifiers = $kind === T_COMMENT ? self::parseComment($text) : null;

            if ($identifiers === []) {
                $errors[$line] = 'A ' . self::MARKER . ' marker needs a comma-separated list of rule identifiers.';
            } elseif ($identifiers !== null && $line === $lastCodeLine) {
                $targets[$line] = array_values(array_unique([...$targets[$line] ?? [], ...$identifiers]));
            } elseif ($identifiers !== null) {
                $markerLines[$line] = $identifiers;
                $pending = [...$pending, ...$identifiers];
            } elseif (in_array($kind, [T_WHITESPACE, T_OPEN_TAG, T_COMMENT, T_DOC_COMMENT], true) === false) {
                if ($pending !== []) {
                    $targets[$line] = array_values(array_unique([...$targets[$line] ?? [], ...$pending]));
                    $pending = [];
                }

                $lastCodeLine = $line + substr_count($text, "\n");
            }

            $line += substr_count($text, "\n");
        }

        return new self($targets, $markerLines, $errors);
    }

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public static function isPhpStanIdentifier(string $identifier): bool
    {
        return str_contains($identifier, '.');
    }

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public static function isRectorIdentifier(string $identifier): bool
    {
        return self::isPhpStanIdentifier($identifier) === false && str_ends_with($identifier, 'Rector');
    }

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public static function isFixerIdentifier(string $identifier): bool
    {
        return self::isPhpStanIdentifier($identifier) === false && self::isRectorIdentifier($identifier) === false;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->targets === [] && $this->markerLines === [] && $this->errors === [];
    }

    /**
     * @param int $line
     * @param string $identifier
     *
     * @return bool
     */
    public function ignores(int $line, string $identifier): bool
    {
        return in_array($identifier, $this->targets[$line] ?? [], true);
    }

    /**
     * @return list<string>
     */
    public function identifiers(): array
    {
        $identifiers = array_unique(array_merge(...array_values($this->targets)));
        sort($identifiers);

        return $identifiers;
    }

    /**
     * The lines a rule must leave alone: the lines its markers ignore, and the markers alone on their line, so the
     * rule cannot move or rewrite the marker away from the line it guards.
     *
     * @param string $identifier
     *
     * @return array<int, true>
     */
    public function protectedLines(string $identifier): array
    {
        return $this->linesNaming($this->targets, $identifier) + $this->linesNaming($this->markerLines, $identifier);
    }

    /**
     * @param array<int, list<string>> $lines
     * @param string $identifier
     *
     * @return array<int, true>
     */
    protected function linesNaming(array $lines, string $identifier): array
    {
        $named = array_filter($lines, fn(array $identifiers): bool => in_array($identifier, $identifiers, true));

        return array_fill_keys(array_keys($named), true);
    }

    /**
     * @param string $comment
     *
     * @return list<string>|null Null when the comment is no marker; an empty list when it is a malformed one.
     */
    protected static function parseComment(string $comment): ?array
    {
        if (str_starts_with($comment, '//')) {
            $text = trim(substr($comment, 2));
        } elseif (str_starts_with($comment, '#')) {
            $text = trim(substr($comment, 1));
        } else {
            return null;
        }

        $rest = Str::after($text, self::MARKER);
        if (str_starts_with($text, self::MARKER) === false || ($rest !== '' && ctype_space($rest[0]) === false)) {
            return null;
        }

        // An optional `-- reason` follows the identifiers.
        $identifiers = array_map(trim(...), explode(',', Str::before($rest, '--')));
        $malformed = collect($identifiers)->contains(fn(string $identifier): bool => $identifier === '' || Str::contains($identifier, [' ', "\t"]));

        return $malformed ? [] : $identifiers;
    }
}
