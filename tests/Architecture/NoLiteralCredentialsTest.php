<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use Simtabi\Laranail\Package\Tools\Testing\AssertsNoLiteralCredentials;

/**
 * Secret scanning cannot tell a test password from a leaked one, and reports both. Tests take
 * their credentials from TestCredentials at run time; this fails on any literal left behind, and
 * on any generated credential whose purpose is not a single plain word.
 */
final class NoLiteralCredentialsTest extends TestCase
{
    use AssertsNoLiteralCredentials;

    /** Measured 2026-10-19: 36 PHP files across tests/ and database/factories/. */
    private const int MIN_FILES = 32;

    public function test_the_tests_hold_no_literal_credentials(): void
    {
        $this->assertNoLiteralCredentialsIn($this->scannedDirectories(), minFiles: self::MIN_FILES);
    }

    public function test_every_generated_credential_is_named_with_a_single_word(): void
    {
        $this->assertCredentialPurposesAreWordsIn($this->scannedDirectories(), minFiles: self::MIN_FILES);
    }

    /** @return list<string> */
    private function scannedDirectories(): array
    {
        return [dirname(__DIR__), dirname(__DIR__, 2) . '/database/factories'];
    }
}
