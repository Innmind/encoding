<?php
declare(strict_types = 1);

namespace Innmind\Encoding\Tar;

use Innmind\Filesystem\{
    File,
    File\Content,
    Directory,
};
use Innmind\MediaType\MediaType;
use Innmind\TimeContinuum\Clock;
use Innmind\Immutable\{
    Str,
    Sequence,
};

/**
 * @see https://packagist.org/packages/pear/archive_tar This class has been reversed engineered from the pear package
 */
final class Encode
{
    private Clock $clock;

    /**
     * @psalm-mutation-free
     */
    private function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    public function __invoke(File|Directory $file): File
    {
        return File::named(
            $file->name()->toString().'.tar',
            Content::ofChunks(
                $this
                    ->encode(
                        $file->name()->str()->toEncoding(Str\Encoding::ascii),
                        $file,
                    )
                    ->add(Str::of(
                        \pack('a1024', ''),
                        Str\Encoding::ascii,
                    )),
            ),
            MediaType::of('application/x-tar'),
        );
    }

    /**
     * @psalm-pure
     */
    public static function of(Clock $clock): self
    {
        return new self($clock);
    }

    /**
     * @return Sequence<Str>
     */
    private function encode(
        Str $path,
        File|Directory $file,
        bool $linkWritten = false,
    ): Sequence {
        return match (true) {
            !$linkWritten && $path->length() > 100 => $this->encodeLongFileName($path, $file),
            $file instanceof File => $this->encodeFile($path, $file),
            $file instanceof Directory => $this->encodeDirectory($path, $file),
        };
    }

    /**
     * @return Sequence<Str>
     */
    private function encodeLongFileName(Str $path, File|Directory $file): Sequence
    {
        $header = $this->header(
            Str::of('././@LongLink', Str\Encoding::ascii),
            $path->length(),
            'link',
        );
        $linkContent = $path
            ->chunk(512)
            ->map(static fn($chunk) => $chunk->map(
                static fn($value) => \pack('a512', $value),
            ));

        return $header
            ->append($linkContent)
            ->append($this->encode($path, $file, true));
    }

    /**
     * @return Sequence<Str>
     */
    private function encodeFile(Str $path, File $file): Sequence
    {
        $size = $file->content()->size()->match(
            static fn($size) => $size->toInt(),
            static fn() => $file
                ->content()
                ->chunks()
                ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
                ->map(static fn($chunk) => $chunk->length())
                ->reduce(
                    0,
                    static fn(int $sum, int $length) => $sum + $length,
                ),
        );
        $header = $this->header($path, $size, File::class);

        return $header->append(
            $file
                ->content()
                ->chunks()
                ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
                ->aggregate(static fn(Str $a, Str $b) => $a->append($b)->chunk(512))
                ->map(static fn($chunk) => \pack('a512', $chunk->toString()))
                ->map(static fn($chunk) => Str::of($chunk, Str\Encoding::ascii)),
        );
    }

    /**
     * @return Sequence<Str>
     */
    private function encodeDirectory(Str $parent, Directory $directory): Sequence
    {
        $header = $this->header($parent, 0, Directory::class);

        $files = $directory
            ->all()
            ->flatMap(fn($file) => $this->encode(
                $parent->append('/')->append($file->name()->str()),
                $file,
            ));

        return $header->append($files);
    }

    /**
     * @param class-string<File>|class-string<Directory>|'link' $type
     *
     * @return Sequence<Str>
     */
    private function header(
        Str $path,
        int $size,
        string $type,
    ): Sequence {
        $fileMode = match ($type) {
            File::class => 000644,
            Directory::class, 'link' => 000755,
        };
        $headerFirstPart = Str::of(
            \pack(
                'a100a8a8a8a12a12',
                $path->toString(), // file name
                \sprintf('%07s', \decoct($fileMode & 000777)), // file mode
                \sprintf('%07s', \decoct(0)), // user id
                \sprintf('%07s', \decoct(0)), // group id
                \sprintf('%011s', \decoct($size)), // file size
                \sprintf('%011s', \decoct($this->clock->now()->milliseconds())), // file last modification time
            ),
            Str\Encoding::ascii,
        );
        $headerLastPart = Str::of(
            \pack(
                'a1a100a6a2a32a32a8a8a155a12',
                match ($type) {
                    File::class => '0',
                    Directory::class => '5',
                    'link' => 'L',
                }, // link indicator
                '', // name of linked file
                'ustar ', // format
                ' ', // format version
                '', // owner user name
                '', // owner group name
                '', // device major number
                '', // device minor number
                '', // filename prefix
                '', // don't know what this is
            ),
            Str\Encoding::ascii,
        );
        $checksum = $headerFirstPart
            ->chunk()
            ->map(static fn($char) => $char->toString())
            ->append(Sequence::strings()->pad(8, ' ')) // checksum placeholder
            ->append(
                $headerLastPart
                    ->chunk()
                    ->map(static fn($char) => $char->toString()),
            )
            ->map(\ord(...))
            ->reduce(
                0,
                static fn(int $sum, int $ord) => $sum + $ord,
            );

        $packedChecksum = Str::of(
            \pack('a8', \sprintf("%06s\0 ", \decoct($checksum))),
            Str\Encoding::ascii,
        );

        return Sequence::lazyStartingWith($headerFirstPart, $packedChecksum, $headerLastPart);
    }
}
