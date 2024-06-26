# Changelog

## [Unreleased]

### Changed

- Requires `innmind/immutable:~5.7`
- The laziness of the tar file is derived from the laziness of input files

### Fixed

- If a file contained a single line with more than `512` characters it wasn't properly encoded in a `tar` file.
- Last modification date of files encoded in a `tar` file were set in the future.

## 1.0.0 - 2023-10-29

### Added

- `Innmind\Encoding\Tar`
- `Innmind\Encoding\Gzip`
