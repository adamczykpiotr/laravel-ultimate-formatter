<?php

declare(strict_types=1);

// spatie/laravel-data isn't installed here; the rename only needs the base class to exist.
// Rector requires bootstrap files once per test, hence the guard.

namespace Spatie\LaravelData;

if (class_exists(Data::class, false) === false) {
    abstract class Data
    {
        /**
         * @return void
         */
        final public function __construct() {}

        /**
         * @param mixed ...$payloads
         *
         * @return static
         */
        public static function from(mixed ...$payloads): static
        {
            return new static();
        }

        /**
         * @param mixed $payload
         *
         * @return static
         */
        public static function validateAndCreate(mixed $payload): static
        {
            return new static();
        }
    }
}
