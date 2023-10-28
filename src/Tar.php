<?php
declare(strict_types = 1);

namespace Innmind\Encoding;

use Innmind\TimeContinuum\Clock;

final class Tar
{
    private function __construct()
    {
    }

    /**
     * @psalm-pure
     */
    public static function encode(Clock $clock): Tar\Encode
    {
        return Tar\Encode::of($clock);
    }
}
