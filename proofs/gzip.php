<?php
declare(strict_types = 1);

use Innmind\Encoding\Gzip;
use Innmind\Filesystem\{
    Adapter\Filesystem,
    File\Content,
    Name,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Monoid\Concat,
    Str\Encoding,
};
use Innmind\BlackBox\Set;

return static function() {
    $files = Set::of('fixtures/symfony.log', 'fixtures/amqp.pdf');

    yield proof(
        'Gzip compression reduce content size',
        given($files->map(\file_get_contents(...))),
        static function($assert, $file) {
            $content = Content::ofString($file);
            $compress = Gzip::compress();

            $compressed = $compress($content);

            $assert
                ->number($content->size()->match(
                    static fn($size) => $size->toInt(),
                    static fn() => null,
                ))
                ->greaterThan($compressed->size()->match(
                    static fn($size) => $size->toInt(),
                    static fn() => null,
                ));
        },
    );

    yield proof(
        'Gzip compression reduce chunks size',
        given($files->map(\file_get_contents(...))),
        static function($assert, $file) {
            $content = Content::ofString($file)->chunks();
            $compress = Gzip::compress();

            $compressed = $compress($content);

            $assert
                ->number(
                    $content
                        ->fold(new Concat)
                        ->toEncoding(Encoding::ascii)
                        ->length(),
                )
                ->greaterThan(
                    $compressed
                        ->fold(new Concat)
                        ->toEncoding(Encoding::ascii)
                        ->length(),
                );
        },
    );

    yield proof(
        'Gzip compress/decompress returns the original content',
        given(Set::either(
            $files->map(\file_get_contents(...)),
            Set::strings()
                ->madeOf(Set::strings()->unicode()->char())
                ->between(0, 2048),
        )),
        static function($assert, $file) {
            $original = Content::ofString($file);
            $compress = Gzip::compress();
            $decompress = Gzip::decompress();

            $content = $decompress($compress($original));

            $assert->same(
                $original->toString(),
                $content->toString(),
            );
        },
    );

    yield proof(
        'Gzip compress/decompress returns the original chunks',
        given(Set::either(
            $files->map(\file_get_contents(...)),
            Set::strings()
                ->madeOf(Set::strings()->unicode()->char())
                ->between(0, 2048),
        )),
        static function($assert, $file) {
            $original = Content::ofString($file)->chunks();
            $compress = Gzip::compress();
            $decompress = Gzip::decompress();

            $content = $decompress($compress($original));

            $assert->same(
                $original->fold(new Concat)->toString(),
                $content->fold(new Concat)->toString(),
            );
        },
    );

    yield proof(
        'Gzip compression always produce the same result',
        given($files->map(\file_get_contents(...))),
        static function($assert, $file) {
            $content = Content::ofString($file);
            $compress = Gzip::compress();

            $compressed1 = $compress($content);
            $compressed2 = $compress($content);

            $assert->same(
                $compressed1->toString(),
                $compressed2->toString(),
            );
        },
    );

    yield proof(
        'Gzip file compression',
        given(
            $files
                ->map(static fn($name) => \substr($name, 9)) // removes 'fixtures/'
                ->map(Name::of(...)),
        ),
        static function($assert, $name) {
            $adapter = Filesystem::mount(Path::of('fixtures/'));
            $original = $adapter->get($name)->match(
                static fn($file) => $file,
                static fn() => null,
            );

            $assert->not()->null($original);

            $compress = Gzip::compress();
            $decompress = Gzip::decompress();

            $compressed = $compress($original);

            $assert
                ->string($compressed->name()->toString())
                ->startsWith($name->toString())
                ->endsWith('.gz');
            $assert->same(
                'application/gzip',
                $compressed->mediaType()->toString(),
            );
            $assert
                ->number($compressed->content()->size()->match(
                    static fn($size) => $size->toInt(),
                    static fn() => null,
                ))
                ->lessThan($original->content()->size()->match(
                    static fn($size) => $size->toInt(),
                    static fn() => null,
                ));

            $decompressed = $decompress($compressed);

            $assert->same($name->toString(), $decompressed->name()->toString());
            $assert->same(
                $original->content()->toString(),
                $decompressed->content()->toString(),
            );
        },
    );
};
