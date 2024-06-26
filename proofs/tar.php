<?php
declare(strict_types = 1);

use Innmind\Encoding\Tar;
use Innmind\Filesystem\{
    Adapter\Filesystem,
    Name,
    File,
    Directory,
};
use Innmind\TimeContinuum\Earth;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Predicate\Instance,
};
use Innmind\BlackBox\Set;
use Fixtures\Innmind\Filesystem\{
    Directory as FDirectory,
    File as FFile,
};

return static function() {
    yield proof(
        'Tar encoding a single file',
        given(Set\Elements::of('amqp.pdf', 'symfony.log')),
        static function($assert, $name) {
            $clock = new Earth\Clock;
            $path = \rtrim(\sys_get_temp_dir(), '/').'/innmind/encoding/';
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
            $path = \rtrim(\sys_get_temp_dir(), '/').'/innmind/encoding/';
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

    yield proof(
        'Tar supports names longer than 100 characters',
        given(
            Set\Strings::madeOf(Set\Chars::alphanumerical())
                ->between(101, 251) // not 255 because it needs to append '.tar'
                ->map(Name::of(...)),
            Set\Strings::madeOf(Set\Chars::alphanumerical())
                ->between(200, 255)
                ->map(Name::of(...)),
        ),
        static function($assert, $name1, $name2) {
            // we use 2 long names to reach a path longer than 512 to make sure
            // the chunking of the path in the link works properly

            $clock = new Earth\Clock;
            $path = \rtrim(\sys_get_temp_dir(), '/').'/innmind/encoding/';
            $tmp = Filesystem::mount(Path::of($path));
            $adapter = Filesystem::mount(Path::of('./'));
            $tar = $adapter
                ->get(Name::of('fixtures'))
                ->map(Directory::of($name2)->add(...))
                ->map(Directory::of($name1)->add(...))
                ->map(Tar::encode($clock))
                ->match(
                    static fn($file) => $file,
                    static fn() => null,
                );

            $tmp->add($tar);

            $exitCode = null;
            \exec("tar -xf $path/{$name1->toString()}.tar --directory=$path", result_code: $exitCode);
            $assert->same(0, $exitCode);

            $assert->true(
                $tmp
                    ->get($name1)
                    ->keep(Instance::of(Directory::class))
                    ->flatMap(static fn($directory) => $directory->get($name2))
                    ->keep(Instance::of(Directory::class))
                    ->flatMap(static fn($directory) => $directory->get(Name::of('fixtures')))
                    ->keep(Instance::of(Directory::class))
                    ->match(
                        static fn($fixtures) => $fixtures->contains(Name::of('amqp.pdf')),
                        static fn() => null,
                    ),
            );
            $assert->true(
                $tmp
                    ->get($name1)
                    ->keep(Instance::of(Directory::class))
                    ->flatMap(static fn($directory) => $directory->get($name2))
                    ->keep(Instance::of(Directory::class))
                    ->flatMap(static fn($directory) => $directory->get(Name::of('fixtures')))
                    ->keep(Instance::of(Directory::class))
                    ->match(
                        static fn($fixtures) => $fixtures->contains(Name::of('symfony.log')),
                        static fn() => null,
                    ),
            );
        },
    );

    yield proof(
        'Tar encode any shape of file/directory',
        given(
            Set\Either::any(
                FFile::any(),
                FDirectory::any(),
            )
                ->filter(
                    static fn($file) => $file
                        ->name()
                        ->str()
                        ->toEncoding(Str\Encoding::ascii)
                        ->length() < 251, // otherwise the `.tar` extension will overflow
                )
                ->filter(
                    static fn($file) => !$file
                        ->name()
                        ->str()
                        ->contains(':'), // if preceded by a letter the tar command will remove the `:` as it interprets it as a windows drive path
                )
                ->filter(
                    static fn($file) => !$file
                        ->name()
                        ->str()
                        ->startsWith('\\'), // the tar command removes leading backslashes
                ),
        ),
        static function($assert, $file) {
            $clock = new Earth\Clock;
            $path = \rtrim(\sys_get_temp_dir(), '/').'/innmind/encoding/';
            $tmp = Filesystem::mount(Path::of($path));
            $tar = Tar::encode($clock)($file);
            $tmp->add($tar);

            $exitCode = null;
            $name = $path.$tar->name()->toString();
            $name = \str_replace("'", "'\\''", $name);
            \exec("tar -xf '$name' --directory=$path", result_code: $exitCode);
            $assert->same(0, $exitCode);

            if ($file instanceof File) {
                $assert->same(
                    $file->content()->toString(),
                    $tmp
                        ->get($file->name())
                        ->keep(Instance::of(File::class))
                        ->match(
                            static fn($file) => $file->content()->toString(),
                            static fn() => null,
                        ),
                );

                return;
            }

            $assert->true($tmp->contains($file->name()));
            // for simplicity no recursive assertions on nested directories
            $file
                ->all()
                ->keep(Instance::of(File::class))
                ->foreach(
                    static fn($expected) => $assert->same(
                        $expected->content()->toString(),
                        $tmp
                            ->get($file->name())
                            ->keep(Instance::of(Directory::class))
                            ->flatMap(static fn($found) => $found->get($expected->name()))
                            ->keep(Instance::of(File::class))
                            ->match(
                                static fn($found) => $found->content()->toString(),
                                static fn() => null,
                            ),
                    ),
                );
        },
    );
};
