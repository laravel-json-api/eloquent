# Change Log

All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## Unreleased

### Added

- [#6](https://github.com/laravel-json-api/eloquent/pull/6) Package now fully supports soft-deleting resources. If a
  model allows soft deleting, but no changes are made to a schema, then deleting the resource will soft-delete it and
  that resource will no longer appear in the API. However, if soft-delete capability is to be exposed to the client, a
  schema should apply the `SoftDeletes` trait from this package and add a `Fields\SoftDelete` field to their list of
  fields. Refer to documentation for full list of capabilities.
- Added the `WithTrashed`, `OnlyTrashed` and `WhereTrashed` filter classes.

### Changed

- **BREAKING** Deleting a model now uses `Model::delete` instead of `Model::forceDelete`. This change was required when
  adding full support for soft-deleting resources.
- **BREAKING** Repositories are now injected with a driver which defines the database interactions for the repository.
  This allows database interactions to be modified, without having to rewrite the repository class - and is used as to
  implement the soft-deletes feature.

### Removed

- **BREAKING** Remove the following methods from the `Schema` class. These were originally added as convenience methods
  if writing custom controller actions - however, their use is now not suitable as all database querying should be
  executed via the repository class to ensure Eloquent query builders are created according to the database driver that
  is in use. The methods are:
    - `Schema::newQuery()`
    - `Schema::query()`

## [1.0.0-alpha.4] - 2021-02-27

### Added

- The Eloquent schema now has `indexQuery` and `relatableQuery` methods. These allow filtering for authorization
  purposes when a list of resources is being retrieved. For instance, it could filter those queries so that only models
  belonging to the authenticated user are returned.
- Can now determine whether multiple filters should return zero-to-one resource using the `Schema::isSingular()` method.

### Changed

- **BREAKING** The query builder classes have been updated for changes to the interfaces they implement. This adds
  the `withRequest()` method and renames `using()` to `withQuery()`.
- [#2](https://github.com/laravel-json-api/eloquent/issues/2) **BREAKING** The `Fillable` contract now type-hints the
  request class in its read-only method signatures, and allows it to be `null`. The `ReadOnly` trait has been updated,
  so this is unlikely to affect field classes if the trait has been used.
- **BREAKING** If no request class is provided to the `ModelHydrator` class via its new  `withRequest()` method, it is
  now assumed the hydration is occurring outside of a HTTP request. I.e. that the developer is manually triggering the
  hydration. Without the HTTP request, fields will not be checked for their read-only state and will be filled if the
  provided data has a value for the field. Implementing libraries must ensure that `withRequest()` is called when
  filling values provided by a HTTP client.
- **BREAKING** Renamed the `Builder` class to `JsonApiBuilder`. This change was made as it was confusing what
  a `Builder` referred to, because Laravel uses this class name for Eloquent builders.
- The `Relation::type()` method should now be used to set the inverse resource type on a relationship field.

### Fixed

- The `Builder::filters()` method now correctly yields both the schema's filter and the filters from a relationship, if
  one is set. Previously the filters were not yielded correctly if there was a relationship.
- the `QueryToOne` and `QueryToMany` builders now correctly use the model's relation name instead of the JSON:API field
  name when retrieving the relationship object from the model. Previously this would have failed with an error if the
  model relationship name was not the same as the JSON:API field name.

### Deprecated

- The `Relation::inverseType()` method is deprecated and will be removed in `1.0-stable`. Use `Relation::type()`
  instead.

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
