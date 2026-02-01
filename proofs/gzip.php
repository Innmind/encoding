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
            $content = Content::ofString($file);
            $compress = Gzip::compress();

            $compressed = $compress($content);

            $assert
                ->number(
                    $content
                        ->chunks()
                        ->fold(Concat::monoid)
                        ->toEncoding(Encoding::ascii)
                        ->length(),
                )
                ->greaterThan(
                    $compressed
                        ->chunks()
                        ->fold(Concat::monoid)
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
            $original = Content::ofString($file);
            $compress = Gzip::compress();
            $decompress = Gzip::decompress();

            $content = $decompress($compress($original));

            $assert->same(
                $original->chunks()->fold(Concat::monoid)->toString(),
                $content->chunks()->fold(Concat::monoid)->toString(),
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
};
