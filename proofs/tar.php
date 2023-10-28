<?php
declare(strict_types = 1);

use Innmind\Encoding\Tar;
use Innmind\Filesystem\{
    Adapter\Filesystem,
    Name,
    Directory,
};
use Innmind\TimeContinuum\Earth;
use Innmind\Url\Path;
use Innmind\Immutable\Predicate\Instance;
use Innmind\BlackBox\Set;

return static function() {
    yield proof(
        'Tar encoding a single file',
        given(Set\Elements::of('amqp.pdf', 'symfony.log')),
        static function($assert, $name) {
            $clock = new Earth\Clock;
            $path = \sys_get_temp_dir().'innmind/encoding/';
            $tmp = Filesystem::mount(Path::of($path));
            $adapter = Filesystem::mount(Path::of('fixtures/'));
            $tar = $adapter
                ->get(Name::of($name))
                ->map(static fn($file) => $file->rename(Name::of('other-'.$name)))
                ->map(Tar::encode($clock))
                ->match(
                    static fn($file) => $file,
                    static fn() => null,
                );

            $assert
                ->string($tar->name()->toString())
                ->startsWith('other-')
                ->contains($name)
                ->endsWith('.tar');
            $assert->same('application/x-tar', $tar->mediaType()->toString());

            $tmp->add($tar);

            $exitCode = null;
            \exec("tar -xf $path/other-$name.tar --directory=$path", result_code: $exitCode);
            $assert->same(0, $exitCode);

            $assert->same(
                $adapter
                    ->get(Name::of($name))
                    ->match(
                        static fn($file) => $file->content()->toString(),
                        static fn() => null,
                    ),
                $tmp
                    ->get(Name::of('other-'.$name))
                    ->match(
                        static fn($file) => $file->content()->toString(),
                        static fn() => null,
                    ),
            );
        },
    );

    yield test(
        'Tar encoding a directory',
        static function($assert) {
            $clock = new Earth\Clock;
            $path = \sys_get_temp_dir().'innmind/encoding/';
            $tmp = Filesystem::mount(Path::of($path));
            $adapter = Filesystem::mount(Path::of('./'));
            $tar = $adapter
                ->get(Name::of('fixtures'))
                ->map(Tar::encode($clock))
                ->match(
                    static fn($file) => $file,
                    static fn() => null,
                );

            $assert->same('fixtures.tar', $tar->name()->toString());
            $assert->same('application/x-tar', $tar->mediaType()->toString());

            $tmp->add($tar);

            $exitCode = null;
            \exec("tar -xf $path/fixtures.tar --directory=$path", result_code: $exitCode);
            $assert->same(0, $exitCode);

            $assert->same(
                $adapter
                    ->get(Name::of('fixtures'))
                    ->keep(Instance::of(Directory::class))
                    ->flatMap(static fn($fixtures) => $fixtures->get(Name::of('amqp.pdf')))
                    ->match(
                        static fn($file) => $file->content()->toString(),
                        static fn() => null,
                    ),
                $tmp
                    ->get(Name::of('fixtures'))
                    ->keep(Instance::of(Directory::class))
                    ->flatMap(static fn($fixtures) => $fixtures->get(Name::of('amqp.pdf')))
                    ->match(
                        static fn($file) => $file->content()->toString(),
                        static fn() => null,
                    ),
            );
            $assert->same(
                $adapter
                    ->get(Name::of('fixtures'))
                    ->keep(Instance::of(Directory::class))
                    ->flatMap(static fn($fixtures) => $fixtures->get(Name::of('symfony.log')))
                    ->match(
                        static fn($file) => $file->content()->toString(),
                        static fn() => null,
                    ),
                $tmp
                    ->get(Name::of('fixtures'))
                    ->keep(Instance::of(Directory::class))
                    ->flatMap(static fn($fixtures) => $fixtures->get(Name::of('symfony.log')))
                    ->match(
                        static fn($file) => $file->content()->toString(),
                        static fn() => null,
                    ),
            );
        },
    );
};
