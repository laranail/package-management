<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Installer\Drivers;

use Illuminate\Support\Facades\Http;
use Simtabi\Laranail\Package\Management\Contracts\SourceDriver;
use Simtabi\Laranail\Package\Management\Installer\RepositoryRef;

/** Downloads a repository tarball from GitHub (`/tarball/{ref}`), Bearer token for private repos. */
final readonly class GithubSourceDriver implements SourceDriver
{
    public function __construct(private int $timeout = 60, private ?string $token = null) {}

    public function supports(RepositoryRef $ref): bool
    {
        return $ref->provider === 'github';
    }

    public function download(RepositoryRef $ref, string $toDir): string
    {
        $url = "https://api.github.com/repos/{$ref->owner}/{$ref->repo}/tarball/{$ref->ref}";

        $request = Http::timeout($this->timeout)
            ->withHeaders(['User-Agent' => 'laranail-package-management', 'Accept' => 'application/vnd.github+json']);

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
