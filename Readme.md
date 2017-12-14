# DreamFactory GraphQL

Use Facebook GraphQL with DreamFactory 2.0. It is based on the PHP implementation [here](https://github.com/webonyx/graphql-php). You can find more information about GraphQL in the [GraphQL Introduction](http://facebook.github.io/react/blog/2015/05/01/graphql-introduction.html) on the [React](http://facebook.github.io/react) blog or you can read the [GraphQL specifications](https://facebook.github.io/graphql/). This is a work in progress.

This package is compatible with Eloquent model (or any other data source). See the example below.

[![Latest Stable Version](https://poser.pugx.org/dreamfactory/df-graphql/v/stable.svg)](https://packagist.org/packages/dreamfactory/df-graphql)
[![Total Downloads](https://poser.pugx.org/dreamfactory/graphql/downloads.svg)](https://packagist.org/packages/dreamfactory/df-graphql)


## Installation

#### Dependencies:

* [DreamFactory Core 0.13.x](https://github.com/dreamfactorysoftware/df-core)
* [GraphQL PHP](https://github.com/webonyx/graphql-php)


**1-** Require the package via Composer in your `composer.json`.
```json
{
	"require": {
		"dreamfactory/df-graphql": "~0.1.0"
	}
}
```

**2-** Run Composer to install or update the new requirement.

```bash
$ composer install
```

or

```bash
$ composer update
```

### DreamFactory 2.11 and up

**1-** Publish the configuration file

```bash
$ php artisan vendor:publish --provider="DreamFactory\Core\GraphQL\ServiceProvider"
```

**2-** Review the configuration file

```
config/graphql.php
```

## Documentation

