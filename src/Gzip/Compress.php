<?php
declare(strict_types = 1);

namespace Innmind\Encoding\Gzip;

use Innmind\Encoding\Gzip\Compress\{
    Context,
    Chunk,
};
use Innmind\Filesystem\File\Content;
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
final class Compress
{
    private function __construct()
    {
    }

    public function __invoke(Content $content): Content
    {
        return Content::ofChunks($this->compressChunks($content->chunks()));
    }

    /**
     * @psalm-pure
     */
    public static function max(): self
    {
        return new self;
    }

    /**
     * @param Sequence<Str> $chunks
     *
     * @return Sequence<Str>
     */
    private function compressChunks(Sequence $chunks): Sequence
    {
        return Sequence::lazy(static function() use ($chunks) {
            // wrapping this context inside a lazy Sequence allows to restart
            // the context everytime the sequence is unwrapped
            $context = Context::new();

            yield $chunks
                ->map(Chunk::data(...))
                ->add(Chunk::finish())
                ->map(static fn($chunk) => $chunk($context));
        })->flatMap(static fn($chunks) => $chunks);
    }
}
