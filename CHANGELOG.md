# Change Log

All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## Unreleased

### Added
- [#1](https://github.com/laravel-json-api/eloquent/pull/1)
Eloquent fields now support serializing models to JSON values. This means that resource classes
now become optional: because in the absence of a resource class, the implementation can fall-back
on serializing resources using the Eloquent schema.
- **BREAKING** Split the `Arr` field class into two: `ArrayList` and `ArrayHash`. This
was required because now that the fields are also serializing values, the handling of empty values
is different depending on whether it is a list (empty array) or a hash (empty array converted
to `null`).

## [1.0.0-alpha.1] - 2021-01-25

Initial release.
