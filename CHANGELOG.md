# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## [0.2.0] - 2025-09-07

### Added

- `ObjectValidator` for validating `stdClass` objects, created with `Validator::isObject()`.
- Coercion support for `ObjectValidator` to convert associative arrays into `stdClass` objects.
- Coercion support for `AssociativeValidator` to convert `stdClass` objects into associative arrays.

### Changed

- **BREAKING**: Renamed `SchemaValidator` to `AssociativeValidator` for better API consistency.
- The factory method `Validator::isAssociative()` now returns an `AssociativeValidator` instance.
- Improved error message for associative array type validation to be more specific.

### Fixed

- Changed the type hint on `FieldValidator::tryValidate()` to `mixed` to correctly accept both array and object payloads as context.

## [0.1.0] - 2025-09-05

### Added

- Initial release of the `lemmon/validator` package.
