<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Installer\Drivers;

use Illuminate\Support\Facades\Http;
use Simtabi\Laranail\Package\Management\Contracts\SourceDriver;
use Simtabi\Laranail\Package\Management\Installer\RepositoryRef;

/** Downloads a repository tarball from Bitbucket (`/get/{ref}.tar.gz`), Bearer token for private repos. */
final readonly class BitbucketSourceDriver implements SourceDriver
{
    public function __construct(private int $timeout = 60, private ?string $token = null) {}

    public function supports(RepositoryRef $ref): bool
    {
        return $ref->provider === 'bitbucket';
    }

    public function download(RepositoryRef $ref, string $toDir): string
    {
        $url = "https://bitbucket.org/{$ref->owner}/{$ref->repo}/get/{$ref->ref}.tar.gz";

        $request = Http::timeout($this->timeout);

        $token = $ref->token ?? $this->token;
        if ($token !== null && $token !== '') {
            $request = $request->withToken($token);
        }

        $body = $request->get($url)->throw()->body();
        $path = rtrim($toDir, DIRECTORY_SEPARATOR) . '/source.tar.gz';
        file_put_contents($path, $body);

        return $path;
    }
}
