<?php

namespace Lms\KSeF;

class KSeFConfig
{
    const AUTH_METHOD_TOKEN = 'token';
    const AUTH_METHOD_CERTIFICATE = 'certificate';

    private $environment;
    private $environmentName;
    private $authMethod;
    private $token;
    private $certificatePath;
    private $certificatePassword;
    private $maxDocuments;
    private $invoiceReferencePageSize;

    private function __construct(
        int $environment,
        string $environmentName,
        string $authMethod,
        ?string $token,
        ?string $certificatePath,
        ?string $certificatePassword,
        int $maxDocuments,
        int $invoiceReferencePageSize
    ) {
        $this->environment = $environment;
        $this->environmentName = $environmentName;
        $this->authMethod = $authMethod;
        $this->token = $token;
        $this->certificatePath = $certificatePath;
        $this->certificatePassword = $certificatePassword;
        $this->maxDocuments = $maxDocuments;
        $this->invoiceReferencePageSize = $invoiceReferencePageSize;
    }

    public static function fromArray(array $config, bool $validateCredentials = true): self
    {
        [$environment, $environmentName] = self::parseEnvironment($config['environment'] ?? 'test');
        $token = self::nullableString($config['token'] ?? null);
        $certificatePath = self::nullableString($config['certificate_path'] ?? null);
        $certificatePassword = self::nullableString($config['certificate_password'] ?? null);
        $authMethod = strtolower(trim(
            $config['auth_method'] ?? ($token === null ? self::AUTH_METHOD_CERTIFICATE : self::AUTH_METHOD_TOKEN)
        ));
        $maxDocuments = min(10000, max(1, (int) ($config['max_documents'] ?? 10000)));
        $invoiceReferencePageSize = min(
            1000,
            max(10, (int) ($config['invoice_reference_page_size'] ?? 1000))
        );

        if (!in_array($authMethod, [self::AUTH_METHOD_TOKEN, self::AUTH_METHOD_CERTIFICATE], true)) {
            throw new \InvalidArgumentException('Unsupported KSeF auth method: ' . $authMethod);
        }

        if ($validateCredentials && $authMethod === self::AUTH_METHOD_TOKEN && $token === null) {
            throw new \InvalidArgumentException('KSeF token is required for token authentication.');
        }

        if ($validateCredentials && $authMethod === self::AUTH_METHOD_CERTIFICATE && $certificatePath === null) {
            throw new \InvalidArgumentException('KSeF certificate path is required for certificate authentication.');
        }

        return new self(
            $environment,
            $environmentName,
            $authMethod,
            $token,
            $certificatePath,
            $certificatePassword,
            $maxDocuments,
            $invoiceReferencePageSize
        );
    }

    public static function fromConfigHelper(string $section = 'ksef', bool $validateCredentials = true): self
    {
        $token = \ConfigHelper::getConfig($section . '.token');

        return self::fromArray([
            'environment' => \ConfigHelper::getConfig($section . '.environment', 'test'),
            'auth_method' => \ConfigHelper::getConfig(
                $section . '.auth_method',
                self::nullableString($token) === null ? self::AUTH_METHOD_CERTIFICATE : self::AUTH_METHOD_TOKEN
            ),
            'token' => $token,
            'certificate_path' => self::resolveCertificatePath(\ConfigHelper::getConfig($section . '.certificate')),
            'certificate_password' => \ConfigHelper::getConfig($section . '.password'),
            'max_documents' => \ConfigHelper::getConfig($section . '.max_documents', 10000),
            'invoice_reference_page_size' => \ConfigHelper::getConfig($section . '.invoice_reference_page_size', 1000),
        ], $validateCredentials);
    }

    public function getEnvironment(): int
    {
        return $this->environment;
    }

    public function getEnvironmentName(): string
    {
        return $this->environmentName;
    }

    public function getAuthMethod(): string
    {
        return $this->authMethod;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getCertificatePath(): ?string
    {
        return $this->certificatePath;
    }

    public function getCertificatePassword(): ?string
    {
        return $this->certificatePassword;
    }

    public function getMaxDocuments(): int
    {
        return $this->maxDocuments;
    }

    public function getInvoiceReferencePageSize(): int
    {
        return $this->invoiceReferencePageSize;
    }

    private static function parseEnvironment($environment): array
    {
        $environment = strtolower(trim((string) $environment));

        switch ($environment) {
            case 'test':
            case '1':
                return [KSeF::ENVIRONMENT_TEST, 'test'];
            case 'prod':
            case 'production':
            case '2':
                return [KSeF::ENVIRONMENT_PROD, 'production'];
            case 'demo':
            case '3':
                return [KSeF::ENVIRONMENT_DEMO, 'demo'];
            default:
                throw new \InvalidArgumentException('Unsupported KSeF environment: ' . $environment);
        }
    }

    private static function nullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function resolveCertificatePath($certificatePath): ?string
    {
        $certificatePath = self::nullableString($certificatePath);
        if ($certificatePath === null) {
            return null;
        }

        return strpos($certificatePath, DIRECTORY_SEPARATOR) === 0
            ? $certificatePath
            : SYS_DIR . DIRECTORY_SEPARATOR . $certificatePath;
    }
}
