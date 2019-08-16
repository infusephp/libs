# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased
### Changed
- Updated currency codes.

## 1.2 - 2019-08-15
### Changed
- satooshi/php-coveralls replaced with to php-coveralls/php-coveralls package.
- Stop using deprecated symfony/event-dispatcher 4.2 and switch to 4.3

### Removed
- PHP 7.0 support

## 1.1 - 2019-06-12
### Added
- Support the `X-HTTP-Method-Override` header.

### Fixed
- No longer send a response body when one has not been supplied.
- Stop using deprecated Twig classes and switch to namespaces (Twig 2.7+)

## 1.0 - 2017-12-02
### Changed
- Require PHP 7.0+
- Drop support for HHVM

### Removed
- Removed `Utility::encryptPassword()`

## 0.6.6 - 2017-11-19
### Changed
- Deprecated `Utility::encryptPassword()`
- Support Symfony 4.

### Fixed
- Do not send a body with 204 response code

## 0.6.5 - 2017-08-04
### Changed
- Change Twig templating extension to `.twig`.

## 0.6.4 - 2017-04-08
### Changed
- Require PHP 5.6+

### Fixed
- `isXhr()` was not detecting the X-Requested-With header correctly

## 0.6.3 - 2016-11-07
### Fixed
- Added missing HTTP status codes
- Gracefully handle sending unassigned HTTP status codes

## 0.6.2 - 2016-10-17
### Changed
- View engines now have HTML escaping enabled by default

### Fixed
- Added missing currency symbols

## 0.6.1 - 2016-08-15
### Changed
- Support Symfony 3.
- Update FastRoute to v1.0.
- Updated currency codes.

## 0.6 - 2016-02-08
### Added
- Locale translation supports fallback phrases
- Added `dispatch()` to Router that only returns the resulting route instead of executing it.
- Added 451 HTTP code to Response.

### Changed
- Update Pimple to v3.

### Removed
- Removed `ErrorStack` class (moved to Pulsar project).
- Removed route resolution logic from Router.
- Removed deprecated methods.

## 0.5 - 2015-12-22
### Added
- `ErrorStack` now behaves like an array.
- Made commonly used array_* functions from `Utility` class available in the global namespace.
- PHP7 support.

### Changed
- Renamed vendor namespace from `infuse` to `Infuse`
- `Router` class is no longer a singleton
- Internally the router now uses [FastRoute](https://github.com/nikic/FastRoute) by nikic
- Setting cookies has been moved from `Request` to `Response`
- Refactored `Queue` class with pluggable drivers

### Removed
- ORM was moved into the [Pulsar](https://github.com/jaredtking/pulsar) project

### Fixed
- Various bug fixes