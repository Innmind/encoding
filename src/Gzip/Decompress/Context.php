<?php
declare(strict_types = 1);

namespace Innmind\Encoding\Gzip\Decompress;

use Innmind\Encoding\Exception\RuntimeException;
use Innmind\Immutable\Str;

/**
 * @internal
 */
final class Context
{
    private \InflateContext $context;

    private function __construct()
    {
        /** @psalm-suppress PossiblyFalsePropertyAssignmentValue */
        $this->context = \inflate_init(
            \ZLIB_ENCODING_GZIP,
            ['level' => 9],
        );
    }

    public static function new(): self
    {
        return new self;
    }

    public function decompress(Str $data): Str
    {
        return $data->map(function($string) use ($data) {
            $decompressed = \inflate_add(
                $this->context,
                $string,
                \ZLIB_NO_FLUSH,
            );

            return match ($decompressed) {
                false => throw new RuntimeException('Failed to decompress data'),
                default => $decompressed,
            };
        });
    }

    public function finish(): Str
    {
        /** @psalm-suppress PossiblyFalseArgument */
        return Str::of(\inflate_add(
            $this->context,
            '',
            \ZLIB_FINISH,
        ));
    }
}
