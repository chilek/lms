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
            'auth_method' => 'token',
            'token' => 'secret-token',
            'max_documents' => '25',
        ]);

        $this->assertSame(KSeF::ENVIRONMENT_TEST, $config->getEnvironment());
        $this->assertSame('test', $config->getEnvironmentName());
        $this->assertSame('token', $config->getAuthMethod());
        $this->assertSame('secret-token', $config->getToken());
        $this->assertSame(25, $config->getMaxDocuments());
    }

    public function testBuildsProductionCertificateConfigFromArray()
    {
        $config = KSeFConfig::fromArray([
            'environment' => 'production',
            'auth_method' => 'certificate',
            'certificate_path' => '/secure/ksef.p12',
            'certificate_password' => 'cert-password',
        ]);

        $this->assertSame(KSeF::ENVIRONMENT_PROD, $config->getEnvironment());
        $this->assertSame('production', $config->getEnvironmentName());
        $this->assertSame('certificate', $config->getAuthMethod());
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

        $this->assertSame('token', $config->getAuthMethod());
        $this->assertSame('secret-token', $config->getToken());
    }

    public function testRejectsUnknownEnvironment()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported KSeF environment');

        KSeFConfig::fromArray([
            'environment' => 'sandbox',
            'auth_method' => 'token',
            'token' => 'secret-token',
        ]);
    }

    public function testRejectsTokenAuthWithoutToken()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('KSeF token is required');

        KSeFConfig::fromArray([
            'environment' => 'test',
            'auth_method' => 'token',
        ]);
    }

    public function testAllowsCredentialValidationToBeDisabledForDryRun()
    {
        $config = KSeFConfig::fromArray([
            'environment' => 'test',
            'auth_method' => 'certificate',
            'max_documents' => 10,
        ], false);

        $this->assertSame(KSeF::ENVIRONMENT_TEST, $config->getEnvironment());
        $this->assertSame('certificate', $config->getAuthMethod());
        $this->assertSame(null, $config->getCertificatePath());
        $this->assertSame(10, $config->getMaxDocuments());
    }

    public function testBuildsInvoiceReferencePageSizeWithApiLimit()
    {
        $config = KSeFConfig::fromArray([
            'environment' => 'test',
            'auth_method' => 'token',
            'token' => 'secret-token',
            'invoice_reference_page_size' => 2000,
        ]);

        $this->assertSame(1000, $config->getInvoiceReferencePageSize());
    }

    public function testBuildsApiBoundedBatchAndPageLimits()
    {
        $config = KSeFConfig::fromArray([
            'environment' => 'test',
            'auth_method' => 'token',
            'token' => 'secret-token',
            'max_documents' => 20000,
            'invoice_reference_page_size' => 1,
        ]);

        $this->assertSame(10000, $config->getMaxDocuments());
        $this->assertSame(10, $config->getInvoiceReferencePageSize());
    }
}
