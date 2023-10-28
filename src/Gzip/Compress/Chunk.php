<?php
declare(strict_types = 1);

namespace Innmind\Encoding\Gzip\Compress;

use Innmind\Immutable\Str;

/**
 * @internal
 */
final class Chunk
{
    private ?Str $data;

    /**
     * @psalm-mutation-free
     */
    private function __construct(?Str $data)
    {
        $this->data = $data;
    }

    public function __invoke(Context $context): Str
    {
        return match ($this->data) {
            null => $context->finish(),
            default => $context->compress($this->data),
        };
    }

    /**
     * @psalm-pure
     */
    public static function data(Str $data): self
    {
        return new self($data);
    }

    /**
     * @psalm-pure
     */
    public static function finish(): self
    {
        return new self(null);
    }
}
