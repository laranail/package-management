<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Contracts;

use Simtabi\Laranail\Package\Management\Installer\RepositoryRef;

/**
 * A VCS source driver fetches an extension's repository archive for a ref. Drivers are
 * resolved by the SourceDriverManager and are host-pluggable via its `extend()`.
 */
interface SourceDriver
{
    public function supports(RepositoryRef $ref): bool;

    /** Download the archive for the ref into $toDir; return the archive file path. */
    public function download(RepositoryRef $ref, string $toDir): string;
}
