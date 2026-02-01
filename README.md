# Encoding

[![Build Status](https://github.com/innmind/encoding/workflows/CI/badge.svg?branch=master)](https://github.com/innmind/encoding/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/innmind/encoding/branch/develop/graph/badge.svg)](https://codecov.io/gh/innmind/encoding)
[![Type Coverage](https://shepherd.dev/github/innmind/encoding/coverage.svg)](https://shepherd.dev/github/innmind/encoding)

This packages allows to encode and compress files and directories without the need for them to be written to the filesystem and never loaded entirely in memory.

> **Note**
> Each file contained in a `tar` file can't exceed an 8Go size.

## Installation

```sh
composer require innmind/encoding
```

## Usage

```php
use Innmind\Filesystem\{
    Adapter,
    Name,
};
use Innmind\Time\Clock;
use Innmind\Url\Path;
use Innmind\Encoding\{
    Gzip,
    Tar,
};

$adapter = Adapter::mount(Path::of('some/directory/'))->unwrap();
$tar = $adapter
    ->get(Name::of('data'))
    ->map(Tar::encode(Clock::live()))
    ->map(Gzip::compress())
    ->match(
        static fn($file) => $file,
        static fn() => null,
    );
```

Here `$tar` represents a `.tar.gz` file content containing all the files and directories from `some/directory/data/`, unless the `data` doesn't exist then it is `null`.

## Documentation

Full documentation can be found at <https://innmind.org/encoding/>.
