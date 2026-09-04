<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Installer\Drivers;

use Illuminate\Support\Facades\Http;
use Simtabi\Laranail\Package\Management\Contracts\SourceDriver;
use Simtabi\Laranail\Package\Management\Installer\RepositoryRef;

/** Downloads a repository tarball from GitLab (archive API), `PRIVATE-TOKEN` for private repos. */
final readonly class GitlabSourceDriver implements SourceDriver
{
    public function __construct(private int $timeout = 60, private ?string $token = null) {}

    public function supports(RepositoryRef $ref): bool
    {
        return $ref->provider === 'gitlab';
    }

    public function download(RepositoryRef $ref, string $toDir): string
    {
        $project = rawurlencode("{$ref->owner}/{$ref->repo}");
        $url = "https://gitlab.com/api/v4/projects/{$project}/repository/archive.tar.gz?sha={$ref->ref}";

        $request = Http::timeout($this->timeout);

        $token = $ref->token ?? $this->token;
        if ($token !== null && $token !== '') {
            $request = $request->withHeaders(['PRIVATE-TOKEN' => $token]);
        }

        $body = $request->get($url)->throw()->body();
        $path = rtrim($toDir, DIRECTORY_SEPARATOR) . '/source.tar.gz';
        file_put_contents($path, $body);

        return $path;
    }
}
