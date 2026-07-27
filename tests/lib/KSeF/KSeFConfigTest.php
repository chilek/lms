<?php

namespace LMS\Tests\KSeF;

if (!defined('STORAGE_DIR')) {
    define('STORAGE_DIR', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lms-ksef-test-storage');
}

if (!class_exists('PHPUnit\Framework\TestCase') && class_exists('PHPUnit_Framework_TestCase')) {
    class_alias('PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
}

use Lms\KSeF\KSeF;
use Lms\KSeF\KSeFConfig;
use PHPUnit\Framework\TestCase;

class KSeFConfigTest extends TestCase
{
    public function testBuildsTestEnvironmentTokenConfigFromArray()
    {
        $config = KSeFConfig::fromArray([
            'environment' => 'test',
            'token' => 'secret-token',
            'max_documents' => '25',
        ]);

        $this->assertSame(KSeF::ENVIRONMENT_TEST, $config->getEnvironment());
        $this->assertTrue($config->usesApiToken());
        $this->assertSame('secret-token', $config->getToken());
        $this->assertSame(25, $config->getMaxDocuments());
    }

    public function testBuildsProductionCertificateConfigFromArray()
    {
        $config = KSeFConfig::fromArray([
            'environment' => 'production',
            'certificate_path' => '/secure/ksef.p12',
            'certificate_password' => 'cert-password',
        ]);

        $this->assertSame(KSeF::ENVIRONMENT_PROD, $config->getEnvironment());
        $this->assertFalse($config->usesApiToken());
        $this->assertSame('/secure/ksef.p12', $config->getCertificatePath());
        $this->assertSame('cert-password', $config->getCertificatePassword());
        $this->assertSame(10000, $config->getMaxDocuments());
    }

    public function testInfersTokenAuthWhenTokenIsConfigured()
    {
        $config = KSeFConfig::fromArray([
            'environment' => 'test',
            'token' => 'secret-token',
        ]);

        $this->assertTrue($config->usesApiToken());
        $this->assertSame('secret-token', $config->getToken());
    }

    public function testRecognizesStandardLmsCertificateSettingWhenItContainsApiToken()
    {
        $token = str_repeat('a', 64);

        $config = KSeFConfig::fromArray([
            'environment' => 'test',
            'certificate_path' => $token,
        ]);

        $this->assertTrue($config->usesApiToken());
        $this->assertSame($token, $config->getToken());
        $this->assertSame(null, $config->getCertificatePath());
    }

    public function testRejectsUnknownEnvironment()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported KSeF environment');

        KSeFConfig::fromArray([
            'environment' => 'sandbox',
            'token' => 'secret-token',
        ]);
    }

    public function testRejectsMissingCredentials()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('KSeF certificate or API token is required');

        KSeFConfig::fromArray([
            'environment' => 'test',
        ]);
    }

    public function testAllowsCredentialValidationToBeDisabledForDryRun()
    {
        $config = KSeFConfig::fromArray([
            'environment' => 'test',
            'max_documents' => 10,
        ], false);

        $this->assertSame(KSeF::ENVIRONMENT_TEST, $config->getEnvironment());
        $this->assertFalse($config->usesApiToken());
        $this->assertSame(null, $config->getCertificatePath());
        $this->assertSame(10, $config->getMaxDocuments());
    }

    public function testBoundsBatchLimitToKSeFApiLimit()
    {
        $config = KSeFConfig::fromArray([
            'environment' => 'test',
            'token' => 'secret-token',
            'max_documents' => 20000,
        ]);

        $this->assertSame(10000, $config->getMaxDocuments());
    }
}
