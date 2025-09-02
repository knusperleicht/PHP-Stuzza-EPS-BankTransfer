<?php
// Copy this file to config.local.php and fill in your real credentials and URLs.
return [
    // EPS interface version (optional). Default in library is '2.6'.
    'interface_version' => '2.6',

    // Merchant credentials
    'user_id' => 'AKLJS231534',
    'pin' => 'topSecret',

    // Beneficiary account details
    'beneficiary_bic' => 'GAWIATW1XXX',
    'beneficiary_iban' => 'AT611904300234573201',
    'beneficiary_name' => 'John Q. Public',

    // Payment flow URLs
    'confirmation_url' => 'https://example.com/eps_confirm.php',
    'ok_url' => 'https://example.com/ThankYou.html',
    'nok_url' => 'https://example.com/Failure.html',

    // Refund settings
    'merchant_iban' => 'AT611904300234573201',

    // EPS transaction id placeholder for refunds demo
    'sample_refund_transaction_id' => 'epsMF2AN0XE2',
];