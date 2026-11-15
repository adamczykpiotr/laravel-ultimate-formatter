<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config;

use InvalidArgumentException;
use LogicException;
use ParseError;
use PhpToken;
use UnexpectedValueException;

/**
 * A config file that returns a literal array, edited as text so the project's values, comments and layout survive.
 * Every edit re-parses the result, so an edit that would break the file throws instead of being written.
 */
class PhpConfigFile
{
    protected const OPENING_BRACKETS = ['(', '[', '{', '${', '#['];
    protected const CLOSING_BRACKETS = [')', ']', '}'];

    /**
     * @param string $contents
     * @param ConfigArray $root
     */
    protected function __construct(
        protected readonly string $contents,
        protected readonly ConfigArray $root,
    ) {}

    /**
     * @param string $contents
     *
     * @return self
     */
    public static function parse(string $contents): self
    {
        try {
            $tokens = PhpToken::tokenize($contents, TOKEN_PARSE);
        } catch (ParseError $error) {
            throw new UnexpectedValueException("it is not valid PHP: {$error->getMessage()}", previous: $error);
        }

        $tokens = array_values(array_filter($tokens, fn(PhpToken $token): bool => $token->isIgnorable() === false));
        foreach ($tokens as $index => $token) {
            if ($token->is(T_RETURN) === false || ($tokens[$index + 1] ?? null)?->text !== '[') {
                continue;
            }

            $position = $index + 1;
            $root = self::parseArray($tokens, $position);
            if (($tokens[$position] ?? null)?->text === ';') {
                return new self($contents, $root);
            }
        }

        throw new UnexpectedValueException('it does not return a literal [...] array');
    }

    /**
     * @return string
     */
    public function contents(): string
    {
        return $this->contents;
    }

    /**
     * The array at the path, the returned array itself for an empty path, or null when a key on the path is missing.
     *
     * @param list<string> $path
     *
     * @return ConfigArray|null
     */
    public function array(array $path): ConfigArray|null
    {
        $array = $this->root;
        foreach ($path as $depth => $key) {
            $entry = $array->entries[$key] ?? null;
            if ($entry === null) {
                return null;
            }

            if ($entry->children === null) {
                $keys = implode('.', array_slice($path, 0, $depth + 1));

                throw new UnexpectedValueException("{$keys} is not a literal [...] array");
            }

            $array = $entry->children;
        }

        return $array;
    }

    /**
     * @param non-empty-list<string> $path
     *
     * @return ConfigEntry|null
     */
    public function entry(array $path): ConfigEntry|null
    {
        $key = $path[array_key_last($path)];

        return $this->array(array_slice($path, 0, -1))?->entries[$key] ?? null;
    }

    /**
     * @param non-empty-list<string> $path
     *
     * @return int|null
     */
    public function intValue(array $path): int|null
    {
        $entry = $this->entry($path);
        if ($entry === null) {
            return null;
        }

        $value = filter_var(substr($this->contents, $entry->valueOffset, $entry->valueLength), FILTER_VALIDATE_INT);

        return is_int($value) ? $value : null;
    }

    /**
     * @param non-empty-list<string> $path
     *
     * @return self
     */
    public function remove(array $path): self
    {
        $entry = $this->requireEntry($path);
        [$start, $end] = $this->wholeLines($entry->keyOffset, $entry->endOffset);

        return self::parse(substr_replace($this->contents, '', $start, $end - $start));
    }

    /**
     * @param non-empty-list<string> $path
     * @param string $newKey
     *
     * @return self
     */
    public function rename(array $path, string $newKey): self
    {
        $entry = $this->requireEntry($path);

        return self::parse(substr_replace($this->contents, self::export($newKey), $entry->keyOffset, $entry->keyLength));
    }

    /**
     * @param non-empty-list<string> $path
     * @param mixed $value
     *
     * @return self
     */
    public function set(array $path, mixed $value): self
    {
        $entry = $this->requireEntry($path);

        return self::parse(substr_replace($this->contents, self::export($value), $entry->valueOffset, $entry->valueLength));
    }

    /**
     * Inserts the key after the $after sibling when it exists, otherwise at the end of the array, on a line of its own.
     *
     * @param list<string> $parentPath
     * @param string $key
     * @param mixed $value
     * @param string|null $after
     *
     * @return self
     */
    public function add(array $parentPath, string $key, mixed $value, string|null $after = null): self
    {
        $parent = $this->array($parentPath) ?? throw new LogicException('The array to add to is missing.');
        $element = self::export($key) . ' => ' . self::export($value) . ',';

        $anchor = ($after === null ? null : $parent->entries[$after] ?? null) ?? $parent->lastEntry();
        if ($anchor === null) {
            return $this->addToEmptyArray($parent, $element);
        }

        // Past a comment that closes the anchor's line, so the comment stays with the anchor.
        $lineEnd = $this->lineEnd($anchor->endOffset);
        $insertAt = $this->isBlankOrComment($anchor->endOffset, $lineEnd) ? $lineEnd : $anchor->endOffset;
        $contents = substr_replace($this->contents, "\n{$this->indentation($anchor->keyOffset)}{$element}", $insertAt, 0);

        if ($anchor->hasComma === false) {
            $contents = substr_replace($contents, ',', $anchor->valueOffset + $anchor->valueLength, 0);
        }

        return self::parse($contents);
    }

    /**
     * @param ConfigArray $parent
     * @param string $element
     *
     * @return self
     */
    protected function addToEmptyArray(ConfigArray $parent, string $element): self
    {
        $indentation = $this->indentation($parent->openOffset);
        $start = $parent->openOffset + 1;
        $inside = substr($this->contents, $start, $parent->closeOffset - $start);

        // An array holding only comments keeps them, so the element goes in before the bracket.
        if (trim($inside) !== '') {
            return self::parse(substr_replace($this->contents, "    {$element}\n{$indentation}", $parent->closeOffset, 0));
        }

        return self::parse(substr_replace($this->contents, "\n{$indentation}    {$element}\n{$indentation}", $start, $parent->closeOffset - $start));
    }

    /**
     * @param non-empty-list<string> $path
     *
     * @return ConfigEntry
     */
    protected function requireEntry(array $path): ConfigEntry
    {
        return $this->entry($path) ?? throw new LogicException(implode('.', $path) . ' is missing.');
    }

    /**
     * Widens the span to whole lines when nothing but whitespace and a trailing comment share them.
     *
     * @param int $start
     * @param int $end
     *
     * @return array{int, int}
     */
    protected function wholeLines(int $start, int $end): array
    {
        $lineStart = $this->lineStart($start);
        $lineEnd = $this->lineEnd($end);
        $ownsLine = trim(substr($this->contents, $lineStart, $start - $lineStart)) === '' && $this->isBlankOrComment($end, $lineEnd);
        if ($ownsLine === false) {
            return [$start, $end];
        }

        return [$lineStart, min($lineEnd + 1, strlen($this->contents))];
    }

    /**
     * @param int $start
     * @param int $end
     *
     * @return bool
     */
    protected function isBlankOrComment(int $start, int $end): bool
    {
        $text = trim(substr($this->contents, $start, $end - $start));

        return $text === '' || str_starts_with($text, '//') || (str_starts_with($text, '#') && str_starts_with($text, '#[') === false);
    }

    /**
     * @param int $offset
     *
     * @return int
     */
    protected function lineStart(int $offset): int
    {
        $newline = strrpos(substr($this->contents, 0, $offset), "\n");

        return $newline === false ? 0 : $newline + 1;
    }

    /**
     * @param int $offset
     *
     * @return int
     */
    protected function lineEnd(int $offset): int
    {
        $newline = strpos($this->contents, "\n", $offset);

        return $newline === false ? strlen($this->contents) : $newline;
    }

    /**
     * @param int $offset
     *
     * @return string
     */
    protected function indentation(int $offset): string
    {
        $line = substr($this->contents, $this->lineStart($offset));

        return substr($line, 0, strspn($line, " \t"));
    }

    /**
     * @param list<PhpToken> $tokens
     * @param int $position The opening bracket, moved past the closing one.
     *
     * @return ConfigArray
     */
    protected static function parseArray(array $tokens, int &$position): ConfigArray
    {
        $openOffset = $tokens[$position]->pos;
        $position++;

        $entries = [];
        while ($tokens[$position]->text !== ']') {
            $key = null;
            $keyToken = $tokens[$position];
            if ($tokens[$position + 1]->is(T_DOUBLE_ARROW)) {
                $key = self::decodeKey($keyToken);
                $position += 2;
            }

            $valueToken = $tokens[$position];
            $children = $valueToken->text === '[' ? self::parseArray($tokens, $position) : null;
            // A value such as [...] + [...] is more than a literal array, so it is left alone.
            if (self::skipValue($tokens, $position)) {
                $children = null;
            }

            $lastValueToken = $tokens[$position - 1];
            $valueEnd = $lastValueToken->pos + strlen($lastValueToken->text);
            $hasComma = $tokens[$position]->text === ',';
            if ($hasComma) {
                $position++;
            }

            if ($key !== null) {
                $entries[$key] = new ConfigEntry(
                    keyOffset: $keyToken->pos,
                    keyLength: strlen($keyToken->text),
                    valueOffset: $valueToken->pos,
                    valueLength: $valueEnd - $valueToken->pos,
                    endOffset: $hasComma ? $valueEnd + 1 : $valueEnd,
                    hasComma: $hasComma,
                    children: $children,
                );
            }
        }

        $closeOffset = $tokens[$position]->pos;
        $position++;

        return new ConfigArray($openOffset, $closeOffset, $entries);
    }

    /**
     * Moves to the comma or bracket that ends the value.
     *
     * @param list<PhpToken> $tokens
     * @param int $position
     *
     * @return bool Whether it moved past any token.
     */
    protected static function skipValue(array $tokens, int &$position): bool
    {
        $start = $position;
        $depth = 0;
        while ($depth > 0 || in_array($tokens[$position]->text, [',', ']'], true) === false) {
            $text = $tokens[$position]->text;
            if (in_array($text, self::OPENING_BRACKETS, true)) {
                $depth++;
            } elseif (in_array($text, self::CLOSING_BRACKETS, true)) {
                $depth--;
            }

            $position++;
        }

        return $position > $start;
    }

    /**
     * @param PhpToken $token
     *
     * @return string|null Null for a key that is not a plain string literal.
     */
    protected static function decodeKey(PhpToken $token): string|null
    {
        if ($token->is(T_CONSTANT_ENCAPSED_STRING) === false) {
            return null;
        }

        $inner = substr($token->text, 1, -1);
        if (str_starts_with($token->text, "'")) {
            return strtr($inner, ['\\\\' => '\\', "\\'" => "'"]);
        }

        return str_contains($inner, '\\') ? null : $inner;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected static function export(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value), is_string($value) => var_export($value, true),
            is_array($value) => self::exportArray($value),
            default => throw new InvalidArgumentException('A config value must be a scalar, null or an array.'),
        };
    }

    /**
     * @param array<int|string, mixed> $values
     *
     * @return string
     */
    protected static function exportArray(array $values): string
    {
        $isList = array_is_list($values);
        $elements = collect($values)
            ->map(fn(mixed $value, int|string $key): string => $isList ? self::export($value) : self::export($key) . ' => ' . self::export($value))
            ->implode(', ');

        return "[{$elements}]";
    }
}
