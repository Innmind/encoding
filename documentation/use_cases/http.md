# Sending compressed files through HTTP

```php
use Innmind\OperatingSystem\Factory;
use Innmind\Filesystem\Name;
use Innmind\Encoding\Gzip;
use Innmind\Http\{
    Request,
    Method,
    ProtocolVersion,
    Headers,
    Header\ContentEncoding,
};
use Innmind\Url\{
    Url,
    Path,
};

$os = Factory::build();
$http = $os->remote()->http();
$os
    ->filesystem()
    ->mount(Path::of('path/to/stored/data/'))
    ->get(Name::of('somefile.txt'))
    ->map(Gzip::compress())
    ->match(
        static fn($file) => $http(Request::of(
            Url::of('https://some-app.tld/upload'),
            Method::post,
            ProtocolVersion::v11,
            Headers::of(
                ContentEncoding::of('gzip'),
            ),
            $file->content(),
        )),
        static fn() => null,
    );
```

If `somefile.txt` exists then it is gzipped and then sent to `https://some-app.tld/upload`. If the file doesn't exist then nothing is done.
