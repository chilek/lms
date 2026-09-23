<?php

namespace Lms\KSeF;

class KSeFConfig
{
    private $environment;
    private $token;
    private $certificatePath;
    private $certificatePassword;
    private $maxDocuments;

    private function __construct(
        int $environment,
        ?string $token,
        ?string $certificatePath,
        ?string $certificatePassword,
        int $maxDocuments
    ) {
        $this->environment = $environment;
        $this->token = $token;
        $this->certificatePath = $certificatePath;
        $this->certificatePassword = $certificatePassword;
        $this->maxDocuments = $maxDocuments;
    }

    public static function fromArray(array $config, bool $validateCredentials = true): self
    {
        $environment = self::parseEnvironment($config['environment'] ?? 'test');
        $token = self::nullableString($config['token'] ?? null);
        $certificatePath = self::nullableString($config['certificate_path'] ?? null);
        $certificatePassword = self::nullableString($config['certificate_password'] ?? null);
        $maxDocuments = min(10000, max(1, (int) ($config['max_documents'] ?? 10000)));

        if ($token === null && $certificatePath !== null && KSeF::isApiToken($certificatePath)) {
            $token = $certificatePath;
            $certificatePath = null;
        }

        if ($validateCredentials && $token === null && $certificatePath === null) {
            throw new \InvalidArgumentException('KSeF certificate or API token is required.');
        }

        return new self(
            $environment,
            $token,
            $certificatePath,
            $certificatePassword,
            $maxDocuments
        );
    }

    public static function fromConfigHelper(bool $validateCredentials = true): self
    {
        $certificateOrToken = KSeF::getCertificatePath();
        $legacyToken = \ConfigHelper::getConfig('ksef.token');

        return self::fromArray([
            'environment' => \ConfigHelper::getConfig('ksef.environment', 'test'),
            'token' => empty($certificateOrToken) ? $legacyToken : null,
            'certificate_path' => $certificateOrToken,
            'certificate_password' => KSeF::getCertificatePassword(),
            'max_documents' => \ConfigHelper::getConfig('ksef.max_documents', 10000),
        ], $validateCredentials);
    }

    public function getEnvironment(): int
    {
        return $this->environment;
    }

    public function usesApiToken(): bool
    {
        return $this->token !== null;
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

    private static function parseEnvironment($environment): int
    {
        $environment = strtolower(trim((string) $environment));

        return match ($environment) {
            'test', '1' => KSeF::ENVIRONMENT_TEST,
            'prod', 'production', '2' => KSeF::ENVIRONMENT_PROD,
            'demo', '3' => KSeF::ENVIRONMENT_DEMO,
            default => throw new \InvalidArgumentException('Unsupported KSeF environment: ' . $environment),
        };
    }

    private static function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
