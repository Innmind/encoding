<?php
declare(strict_types = 1);

namespace Innmind\Encoding;

final class Gzip
{
    private function __construct()
    {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function compress(): Gzip\Compress
    {
        return Gzip\Compress::max();
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function decompress(): Gzip\Decompress
    {
        return Gzip\Decompress::max();
    }
}
