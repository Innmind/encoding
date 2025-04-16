<?php
declare(strict_types = 1);

namespace Innmind\Encoding\Gzip\Compress;

use Innmind\Encoding\Exception\RuntimeException;
use Innmind\Immutable\Str;

/**
 * @internal
 */
final class Context
{
    private \DeflateContext $context;

    private function __construct()
    {
        /** @psalm-suppress PossiblyFalsePropertyAssignmentValue */
        $this->context = \deflate_init(
            \ZLIB_ENCODING_GZIP,
            ['level' => 9],
        );
    }

    public static function new(): self
    {
        return new self;
    }

    public function compress(Str $data): Str
    {
        return $data->map(function($string) use ($data) {
            $compressed = \deflate_add(
                $this->context,
                $string,
                \ZLIB_NO_FLUSH,
            );

            return match ($compressed) {
                false => throw new RuntimeException('Failed to compress data'),
                default => $compressed,
            };
        });
    }

    public function finish(): Str
    {
        /** @psalm-suppress PossiblyFalseArgument */
        return Str::of(\deflate_add(
            $this->context,
            '',
            \ZLIB_FINISH,
        ));
    }
}
