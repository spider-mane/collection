# Collection

## A message to Russian 🇷🇺 people

If you currently live in Russia, please read [this message][link-to-russia].

[![Stand With Ukraine][banner-support-ukraine]][link-support-ukraine]

[![Stand With Ukraine][badge-support-ukraine]][link-support-ukraine]
[![Latest Version on Packagist][badge-packagist-version]][link-packagist]
[![Build Status][badge-scrutinizer-build]][link-scrutinizer]
[![Code Coverage][badge-scrutinizer-coverage]][link-scrutinizer]
[![Total Downloads][badge-packagist-downloads]][link-packagist-downloads]
[![Software License][badge-license]](LICENSE.md)
[![Required PHP Version][badge-packagist-php]][link-php]

## Purpose

This package facilitates the creation of typed collections without having to duplicate underlying logic. Due to PHP's enforcement of [Liskov Substitution Principle](https://php.watch/articles/php-lsp), it is not possible to create a natively typed collection using inheritance so long as you need methods that operate on specific types. This package provides a configurable 'collection kernel' class that stores and performs operations on a given array. Rather than inheritance, this paradigm uses (self-determined) composition to fulfil basic collection functionality. Besides enabling strong typing, one of the many benefits of this approach is that you have complete control over the operations to be exposed by your collections.

## Installation

Via Composer

```bash
composer require webtheory/collection
```

## Usage

```php

```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email spider.mane.web@gmail.com instead of using the issue tracker.

## Credits

* [Chris Williams][link-author]
* [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

<!-- Links -->
[link-author]: https://github.com/spider-mane
[link-contributors]: ../../contributors
[link-packagist]: https://packagist.org/packages/webtheory/collection
[link-packagist-downloads]: https://packagist.org/packages/webtheory/collection/stats
[link-php]: https://php.net
[link-scrutinizer]: https://scrutinizer-ci.com/g/spider-mane/collection

<!-- Badges -->
[badge-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg
[badge-packagist-downloads]: https://img.shields.io/packagist/dt/webtheory/collection.svg
[badge-packagist-php]: https://img.shields.io/packagist/php-v/webtheory/collection.svg?colorB=%238892BF
[badge-packagist-version]: https://img.shields.io/packagist/v/webtheory/collection.svg
[badge-scrutinizer-build]: https://img.shields.io/scrutinizer/build/g/spider-mane/collection.svg
[badge-scrutinizer-coverage]: https://img.shields.io/scrutinizer/coverage/g/spider-mane/collection.svg
[badge-scrutinizer-quality]: https://img.shields.io/scrutinizer/g/spider-mane/collection.svg

<!-- Support Ukraine -->
[badge-support-ukraine]: https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/badges/StandWithUkraine.svg
[banner-support-ukraine]: https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg
[link-support-ukraine]: https://stand-with-ukraine.pp.ua
[link-to-russia]: https://github.com/vshymanskyy/StandWithUkraine/blob/main/docs/ToRussianPeople.md
