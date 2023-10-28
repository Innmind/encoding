<?php
declare(strict_types = 1);

use Innmind\Encoding\Gzip;
use Innmind\Filesystem\File\Content;
use Innmind\Immutable\{
    Monoid\Concat,
    Str\Encoding,
};
use Innmind\BlackBox\Set;

return static function() {
    yield proof(
        'Gzip compression reduce content size',
        given(
            Set\Elements::of('fixtures/symfony.log', 'fixtures/amqp.pdf')
                ->map(\file_get_contents(...)),
        ),
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
        given(
            Set\Elements::of('fixtures/symfony.log', 'fixtures/amqp.pdf')
                ->map(\file_get_contents(...)),
        ),
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
        given(Set\Either::any(
            Set\Elements::of('fixtures/symfony.log', 'fixtures/amqp.pdf')
                ->map(\file_get_contents(...)),
            Set\Strings::madeOf(Set\Unicode::any())->between(0, 2048),
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
        given(Set\Either::any(
            Set\Elements::of('fixtures/symfony.log', 'fixtures/amqp.pdf')
                ->map(\file_get_contents(...)),
            Set\Strings::madeOf(Set\Unicode::any())->between(0, 2048),
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
};
