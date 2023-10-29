# Getting started

This package allows to create `tar` files and compress any file via `gzip` all in memory. This means that you don't have to have the source files written to disk to compress them.

This package also work lazily, meaning that you never have to load a whole file in memory allowing you to work with files that may not fit in memory.

Combining this package with the rest of the [Innmind ecosystem](https://github.com/innmind/) unlocks opportunities that weren't possible previously (or at least very hard to achieve). Here are some use cases:

- [Creating a backup from different sources](use_cases/backup.md)
- [Storing compressed files](use_cases/compressed_at_rest.md)
- [Sending compressed files through HTTP](use_cases/http.md)

> **Note**
> All use cases use the [`innmind/operating-system`](https://packagist.org/packages/innmind/operating-system) package.
