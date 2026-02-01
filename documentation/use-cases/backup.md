# Creating a backup from different sources

```php
use Innmind\OperatingSystem\Factory;
use Formal\AccessLayer\Query\SQL;
use Innmind\Filesystem\{
    Name,
    File\,
    File\Content\Line,
    Directory,
};
use Innmind\MediaType\MediaType;
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Immutable\Str;
use Innmind\Encoding\{
    Gzip,
    Tar,
};

$os = Factory::build();

$data = $os
    ->filesystem()
    ->mount(Path::of('path/to/stored/data'))
    ->unwrap()
    ->root()
    ->rename(Name::of('data'));
$sql = $os
    ->remote()
    ->sql(Url::of('mysql://{user}:{password}@localhost:3306/{database}'))
    ->unwrap();
$users = $sql(SQL::onDemand('SELECT * FROM users'))
    ->map(static fn($row) => \implode(
        "\t",
        \array_values($row->toArray()),
    ))
    ->map(Str::of(...))
    ->map(Line::of(...));

$archive = Gzip::compress()(
    Tar::encode($os->clock())(
        Directory::named('archive')
            ->add($data)
            ->add(File::named(
                'users.tsv',
                $users,
                MediaType::of('text/tab-separated-values'),
            )),
    ),
);
```

Up to this point `$archive` represents a file content but no real operation has been done. For the real compression to happen you need to _unwrap_ the file either by persisting to the filesystem, sending it through HTTP/AMQP or returning it as an HTTP response. Here's an example of a simple file responding to an HTTP request:

```php
use Innmind\Http\{
    Response\Sender\Native as ResponseSender,
    Response,
    Response\StatusCode,
    ProtocolVersion,
    Headers,
    Header\ContentType,
};

$archive = /* see above */;

ResponseSender::of($os->clock())(Response::of(
    StatusCode::ok,
    ProtocolVersion::v11,
    Headers::of(
        ContentType::of('application', 'octet-stream')
    ),
    $archive,
));
```

And that's it ! `ResponseSender` will stream the archive chunk by chunk.
