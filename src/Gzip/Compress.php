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

    /**
     * @template T of Content|Sequence<Str>
     *
     * @param T $content
     *
     * @return T
     */
    public function __invoke(Content|Sequence $content): Content|Sequence
    {
        /**
         * @psalm-suppress PossiblyInvalidArgument For some reason it doesn't understand the Sequence check
         * @var T
         */
        return match (true) {
            $content instanceof Content => $this->compressContent($content),
            $content instanceof Sequence => $this->compressChunks($content),
        };
    }

    /**
     * @psalm-pure
     */
    public static function max(): self
    {
        return new self;
    }

    private function compressContent(Content $content): Content
    {
        return Content::ofChunks($this->compressChunks($content->chunks()));
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
