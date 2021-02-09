# Change Log

All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## [1.0.0-alpha.3] - 2021-02-09

### Added

- Relationship fields now implement the new `isValidated()` method, indicating whether the field value should be merged
  with client provided values for an update request. By default, the `BelongsTo` and `MorphTo` relations *are*
  validated, whereas all other fields are not. This is a sensible default, as the `BelongsTo/MorphTo` identifiers are
  stored on the model so are likely to be required for validation. The defaults can be overidden on the fields using
  the `mustValidate()` or `notValidated()` methods.
- Eager loading now supports schemas having default eager load paths. This is set via the `$with` property on the
  schema, which is returned by the public `with()` method.

### Changed

- Moved the existing `EagerLoader`, `EagerLoadMorphs` and `EagerLoadPath` to the `EagerLoading` namespace.

### Fixed

- The `BelongsToMany` field now correctly yields both its own filters and filters from its pivot fields. Previously the
  filters were not yielded correctly if both the field and pivot had filters.

## [1.0.0-alpha.2] - 2021-02-02

### Added

- [#1](https://github.com/laravel-json-api/eloquent/pull/1)
  Eloquent fields now support serializing models to JSON values. This means that resource classes now become optional:
  because in the absence of a resource class, the implementation can fall-back on serializing resources using the
  Eloquent schema.
- **BREAKING** Split the `Arr` field class into two: `ArrayList` and `ArrayHash`. This was required because now that the
  fields are also serializing values, the handling of empty values is different depending on whether it is a list (empty
  array) or a hash (empty array converted to `null`).
- Can now set the URI field name for a relationship on the schema's relationship field, using the
  `withUriFieldName()` method. Alternatively, the `retainFieldName()` method can be used to retain the field name as-is.

## [1.0.0-alpha.1] - 2021-01-25

Initial release.
