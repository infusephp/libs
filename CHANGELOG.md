# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased
### Changed
- Require PHP 5.6+

### Fixed
- `isXhr()` was not detecting the X-Requested-With header correctly

## 0.6.3 - 2016-11-07
### Fixed
- Added missing HTTP status codes

### Fixed
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