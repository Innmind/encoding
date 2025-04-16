---
hide:
    - navigation
    - toc
---

# Getting started

This package allows to create `tar` files and compress any file via `gzip` all in memory. This means that you don't have to have the source files written to disk to compress them.

This package also work lazily, meaning that you never have to load a whole file in memory allowing you to work with files that may not fit in memory.

Combining this package with the rest of the [Innmind ecosystem](https://github.com/innmind/) unlocks opportunities that weren't possible previously (or at least very hard to achieve).

!!! note ""
    All use cases use the [`innmind/operating-system`](https://packagist.org/packages/innmind/operating-system) package.
