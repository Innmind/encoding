<?php
declare(strict_types = 1);

namespace Innmind\Encoding\Gzip;

use Innmind\Encoding\Gzip\Decompress\{
    Context,
    Chunk,
};
use Innmind\Filesystem\{
    File,
    File\Content,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
final class Decompress
{
    private function __construct()
    {
    }

    /**
     * @template T of File|Content|Sequence<Str>
     *
     * @param T $content
     *
     * @return T
     */
    public function __invoke(File|Content|Sequence $content): File|Content|Sequence
    {
        /**
         * @psalm-suppress PossiblyInvalidArgument For some reason it doesn't understand the Sequence check
         * @var T
         */
        return match (true) {
            $content instanceof File => $this->decompressFile($content),
            $content instanceof Content => $this->decompressContent($content),
            $content instanceof Sequence => $this->decompressChunks($content),
        };
    }

    /**
     * @psalm-pure
     */
    public static function max(): self
    {
        return new self;
    }

    private function decompressFile(File $file): File
    {
        $name = $file->name()->str();

        /** @psalm-suppress ArgumentTypeCoercion */
        return File::named(
            match ($name->endsWith('.gz') && $name->length() !== 3) {
                true => $name->dropEnd(3)->toString(),
                false => $name->toString(),
            },
            $this->decompressContent($file->content()),
        );
    }

    private function decompressContent(Content $content): Content
    {
        return Content::ofChunks($this->decompressChunks($content->chunks()));
    }

    /**
     * @param Sequence<Str> $chunks
     *
     * @return Sequence<Str>
     */
    private function decompressChunks(Sequence $chunks): Sequence
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
