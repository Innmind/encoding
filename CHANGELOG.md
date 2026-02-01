# Changelog

## [Unreleased]

### Changed

- Requires PHP `8.4`
- Requires `innmind/filesystem:~9.0`
- Requires `innmind/time:~1.0`

## 2.0.0 - 2025-04-16

### Changed

- Requires `innmind/filesystem:~8.0`
- Requires `innmind/time-continuum:~4.1`
- `Innmind\Encoding\Tar\Encode::_invoke()` now only returns a file content

### Removed

- Ability to Gzip (de)compress a `File` or a `Sequence` of chunks

## 1.1.0 - 2024-06-26

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
