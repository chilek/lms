<?php

namespace Lms\Google2FA;

trait QRCode
{
    /**
     * Creates a QR code url.
     *
     * @param string $company
     * @param string $holder
     * @param string $secret
     *
     * @return string
     */
    public function getQRCodeUrl(
        $company,
        $holder,
        #[\SensitiveParameter]
        $secret
    ) {
        return 'otpauth://totp/'.
            rawurlencode($holder).
            '?secret='.
            $secret.
            '&issuer='.
            rawurlencode($company).
            '&algorithm='.
            rawurlencode(strtoupper($this->getAlgorithm())).
            '&digits='.
            rawurlencode(strtoupper((string) $this->getOneTimePasswordLength())).
            '&period='.
            rawurlencode(strtoupper((string) $this->getKeyRegeneration())).
            '';
    }
}
