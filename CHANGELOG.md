# Changelog

## [1.13.6](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.13.5...v1.13.6) (2026-11-15)


### Bug Fixes

* format config, database and routes in applications ([#48](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/48)) ([934e511](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/934e5118204a1140cb9e8dcd1709f136ccbe5239)), closes [#18](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/18)

## [1.13.5](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.13.4...v1.13.5) (2026-11-15)


### Bug Fixes

* **check:** find the PHPStan ruleset when run with -q ([#46](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/46)) ([7e19417](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/7e194176acb5ac5a97f2360357a0f4934b315ace))

## [1.13.4](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.13.3...v1.13.4) (2026-11-15)


### Bug Fixes

* run the package's own PHPStan ruleset on itself ([#45](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/45)) ([f958df9](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/f958df9fc29315d4ea46a79ca7e6bf08fb472be4))

## [1.13.3](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.13.2...v1.13.3) (2026-11-15)


### Bug Fixes

* **check:** fail when PHPStan doesn't load the formatter's ruleset ([#44](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/44)) ([91e6dcb](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/91e6dcb9df28b74654bff7f997711a9dbf4a8fce))

## [1.13.2](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.13.1...v1.13.2) (2026-11-15)


### Bug Fixes

* migrate pre-1.13 configs, and run migrate-config without a composer script ([#43](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/43)) ([e0324a0](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/e0324a0353bdf49579ae07a0833f6351b788064b))

## [1.13.1](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.13.0...v1.13.1) (2026-11-15)


### Bug Fixes

* keep [@use](https://github.com/use) docblocks when grouping traits, stop passing -q/-v to Rector ([#42](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/42)) ([2fc8fe3](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/2fc8fe354c2c7b83d7e10c17601d8242a6a8ae2e))

# [1.13.0](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.12.0...v1.13.0) (2026-11-15)


### Features

* **check:** custom PHPStan rules for migrations, resources, enums, jobs and models ([#34](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/34)) ([dc13fd2](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/dc13fd266f23dc0c571ecd31bac41c7e1281cfcc)), closes [#23](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/23)

# [1.12.0](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.11.0...v1.12.0) (2026-11-15)


### Features

* migrate the published config on package upgrade ([#41](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/41)) ([b0afc50](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/b0afc506bd7084ebbe49b6197bb5acbf5ced8a9a)), closes [#38](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/38)

# [1.11.0](https://github.com/adamczykpiotr/laravel-ultimate-formatter/compare/v1.10.0...v1.11.0) (2026-11-15)


### Features

* add 7 custom Rector rules (explicit comparisons, interpolation, grouped traits, factory constants, early continue, foreach naming, coalesce) ([#37](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/37)) ([f337e7e](https://github.com/adamczykpiotr/laravel-ultimate-formatter/commit/f337e7e708cc44d5a0df0650e962fb215ec04d1d)), closes [#21](https://github.com/adamczykpiotr/laravel-ultimate-formatter/issues/21)

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
