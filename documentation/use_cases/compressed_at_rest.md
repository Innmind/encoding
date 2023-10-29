# Storing compressed files

In many applications we allow our users to upload files. Storing them can take up a lot of space.

Instead of storing these files as is, we could store them gzipped to save space and return them gzipped or uncompressed depending of the supported features.

Here's an example:

```php
use Innmind\OperatingSystem\Factory;
use Innmind\Http\Factory\ServerRequest\ServerRequestFactory;
use Innmind\Filesystem\Name;
use Innmind\Encoding\Gzip;
use Innmind\Url\Path;
use Innmind\Http\{
    Response,
    Response\StatusCode,
    ResponseSender,
};

$os = Factory::build();
$serverRequest = ServerRequestFactory::default($os->clock())();

$response = $serverRequest
    ->files()
    ->under('tsv')
    ->get('users')
    ->map(static fn($file) => $file->rename(Name::of('users.tsv'))
    ->map(Gzip::compress())
    ->map(
        static fn($file) => $os
            ->filesystem()
            ->mount(Path::of('path/to/stored/data/'))
            ->add($file)),
    )
    ->match(
        static fn() => Response::of(
            StatusCode::created,
            $serverRequest->protocolVersion(),
        ),
        static fn() => Response::of(
            StatusCode::badRequest,
            $serverRequest->protocolVersion(),
        ),
    );

(new ResponseSender($os->clock()))($response);
```

This code will take any file uploaded in the key `tsv[users]`, gzip it and write it in the `path/to/stored/data/` directory under the name `users.tsv.gz` (`Gzip::compress()` automatically add the suffix `.gz`) and return a `201` HTTP response. If the upload failed it will return a `400` response.

And for the code streaming this file:

```php
use Innmind\OperatingSystem\Factory;
use Innmind\Http\Factory\ServerRequest\ServerRequestFactory;
use Innmind\Filesystem\Name;
use Innmind\Encoding\Gzip;
use Innmind\Url\Path;
use Innmind\Http\{
    Response,
    Response\StatusCode,
    ResponseSender,
    Headers,
    Header\ContentType,
    Header\ContentEncoding,
};

$os = Factory::build();
$serverRequest = ServerRequestFactory::default($os->clock())();

$acceptGzip = $serverRequest
    ->headers()
    ->get('accept-encoding')
    ->map(static fn($header): bool => $header->values()->any(
        static fn($value) => $value->toString() === 'gzip',
    ))
    ->match(
        static fn(bool $accept): bool => $accept,
        static fn() => false,
    );

$response = $os
    ->filesystem()
    ->mount(Path::of('path/to/stored/data/'))
    ->get(Name::of('users.tsv.gz'))
    ->map(static fn($file) => match ($acceptGzip) {
        true => Response::of(
            StatusCode::ok,
            $serverRequest->protocolVersion(),
            Headers::of(
                ContentEncoding::of('gzip'),
                ContentType::of('text', 'tab-separated-values'),
            ),
            $file->content(),
        ),
        false => Response::of(
            StatusCode::ok,
            $serverRequest->protocolVersion(),
            Headers::of(
                ContentType::of('text', 'tab-separated-values'),
            ),
            Gzip::decompress()($file->content()),
        ),
    })
    ->match(
        static fn($response) => $response,
        static fn() => Response::of(
            StatusCode::notFound,
            $serverRequest->protocolVersion(),
        ),
    );

(new ResponseSender($os->clock()))($response);
```

Here we try to load the `users.tsv.gz` file, we check if the caller accepts a gzipped content, if so we return the file as is via a `200` HTTP response and if not we decompress the file and return it. And if the file doesn't exist we return a `400` response.
