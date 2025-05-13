<?php

namespace Lms\Google2FA;

use PragmaRX\Google2FA\Exceptions\InvalidAlgorithmException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Support\Base32;
use PragmaRX\Google2FA\Support\Constants;
use PragmaRX\Google2FA\Support\QRCode;
use PragmaRX\Google2FA\Google2FA AS PragmaRXGoogle2FA;

class Google2FA extends PragmaRXGoogle2FA
{
    use \Lms\Google2FA\QRCode;
}
