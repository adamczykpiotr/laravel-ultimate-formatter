# Changelog

# [1.10.0](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.9.0...v1.10.0) (2026-11-15)


### Features

* enable more Rector and PHP CS Fixer rules, guard against conflicting sets ([#36](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/36)) ([e990bbb](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/e990bbbee5e09b693284513b6d991dc6a57b4035)), closes [#19](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/19)

# [1.9.0](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.8.0...v1.9.0) (2026-11-15)


### Features

* simplify quiet/verbose flags passing to commands underneath ([#33](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/33)) ([9313b84](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/9313b8452d36c97b7710600c80cccd29f2729b36))

# [1.8.0](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.7.0...v1.8.0) (2026-11-15)


### Features

* ship laravel-ide-helper and publish its config ([#32](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/32)) ([ae8887f](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/ae8887f1d728f830071f644a7ee83e25811ee1b2))

# [1.7.0](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.6.1...v1.7.0) (2026-11-15)


### Features

* architecture testing ([#24](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/24)) ([3788f72](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/3788f72535a79b726f0d3efa4cc6749e8c98844a)), closes [#22](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/22)

## [1.6.1](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.6.0...v1.6.1) (2026-11-10)


### Bug Fixes

* Rector 2.8.2 minimum, stop NormalizeMethodDocblockRector reporting no-op changes ([#30](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/30)) ([0620fcf](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/0620fcf4816f93c25138f448f4ff9aaeda628ae6))

# [1.6.0](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.5.0...v1.6.0) (2026-11-10)


### Features

* import global classes, keep self/static/parent unqualified in all docblock tags ([#31](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/31)) ([1ddb46e](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/1ddb46eb4da9853e2cc725c0e3b7cd88f8f1be54))

# [1.5.0](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.4.0...v1.5.0) (2026-10-30)


### Features

* upgrade to Laravel 12.x minimum, cleanup unqualified `\self` types ([#25](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/25)) ([8358ab1](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/8358ab1e1aa5dc580cc93577aa0713b9dfa2e3c0))

# [1.4.0](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.3.0...v1.4.0) (2026-10-28)


### Features

* **install:** fill the gaps when installing in a package ([#14](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/14)) ([0830da3](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/0830da3597fa55b61c6e3fd25f23f746e81d5af6)), closes [#13](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/13)

# [1.3.0](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.2.1...v1.3.0) (2026-10-27)


### Features

* **perf:** run PHP CS Fixer on every available core ([#7](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/7)) ([f525212](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/f525212b8f1352534349d24ef232689747f12f93)), closes [#5](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/5)

## [1.2.1](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.2.0...v1.2.1) (2026-10-27)


### Bug Fixes

* enforce LF line endings via .gitattributes ([#9](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/9)) ([3aa3740](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/3aa374084120ff79341af7bea32a68104c27dc74))

# [1.2.0](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.1.0...v1.2.0) (2026-10-27)


### Features

* upgrade PHPStan ^2.3 and Rector ^2.8 for performance improvements ([#8](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/8)) ([7a91efc](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/7a91efc99b6a287f75d922b8d5d06fec7ebcc5a1)), closes [#6](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/6)

# [1.1.0](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.0.0...v1.1.0) (2026-10-25)


### Features

* add configurable pre steps to format ([#4](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/4)) ([a522a40](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/a522a40885dccb92d9920351b2c643336d440e83))

# 1.0.0 (2026-10-25)


### Features

* ship shared formatting tooling with an install command ([#2](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/2)) ([c4cde7e](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/c4cde7e462137d09ce11e1fde233e6c4880c78ac))
