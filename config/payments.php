<?php

$legacyEwalletKeys = array_values(array_filter(array_map(
    static fn (string $name): ?string => match (strtolower(trim($name))) {
        'gopay', 'go pay' => 'gopay',
        'ovo' => 'ovo',
        'dana' => 'dana',
        default => null,
    },
    preg_split('/[,\/|]+/', (string) env('PAYMENT_EWALLET_NAME', ''), -1, PREG_SPLIT_NO_EMPTY),
)));

$methods = [
    'bca_va' => [
        'category' => 'bank',
        'label' => 'Transfer Bank BCA',
        'short_label' => 'BCA',
        'recipient' => env('PAYMENT_BCA_VA_NUMBER') ?: env('PAYMENT_BCA_NUMBER'),
        'account_name' => env('PAYMENT_BCA_ACCOUNT_NAME') ?: env('PAYMENT_BCA_HOLDER'),
    ],
    'mandiri_va' => [
        'category' => 'bank',
        'label' => 'Transfer Bank Mandiri',
        'short_label' => 'MDR',
        'recipient' => env('PAYMENT_MANDIRI_VA_NUMBER') ?: env('PAYMENT_MANDIRI_NUMBER'),
        'account_name' => env('PAYMENT_MANDIRI_ACCOUNT_NAME') ?: env('PAYMENT_MANDIRI_HOLDER'),
    ],
    'bri_va' => [
        'category' => 'bank',
        'label' => 'Transfer Bank BRI',
        'short_label' => 'BRI',
        'recipient' => env('PAYMENT_BRI_VA_NUMBER') ?: env('PAYMENT_BRI_NUMBER'),
        'account_name' => env('PAYMENT_BRI_ACCOUNT_NAME') ?: env('PAYMENT_BRI_HOLDER'),
    ],
    'gopay' => [
        'category' => 'ewallet',
        'label' => 'GoPay',
        'short_label' => 'GP',
        'recipient' => env('PAYMENT_GOPAY_RECIPIENT') ?: (in_array('gopay', $legacyEwalletKeys, true) ? env('PAYMENT_EWALLET_NUMBER') : null),
        'account_name' => env('PAYMENT_GOPAY_ACCOUNT_NAME') ?: (in_array('gopay', $legacyEwalletKeys, true) ? env('PAYMENT_EWALLET_HOLDER') : null),
    ],
    'ovo' => [
        'category' => 'ewallet',
        'label' => 'OVO',
        'short_label' => 'OVO',
        'recipient' => env('PAYMENT_OVO_RECIPIENT') ?: (in_array('ovo', $legacyEwalletKeys, true) ? env('PAYMENT_EWALLET_NUMBER') : null),
        'account_name' => env('PAYMENT_OVO_ACCOUNT_NAME') ?: (in_array('ovo', $legacyEwalletKeys, true) ? env('PAYMENT_EWALLET_HOLDER') : null),
    ],
    'dana' => [
        'category' => 'ewallet',
        'label' => 'DANA',
        'short_label' => 'DANA',
        'recipient' => env('PAYMENT_DANA_RECIPIENT') ?: (in_array('dana', $legacyEwalletKeys, true) ? env('PAYMENT_EWALLET_NUMBER') : null),
        'account_name' => env('PAYMENT_DANA_ACCOUNT_NAME') ?: (in_array('dana', $legacyEwalletKeys, true) ? env('PAYMENT_EWALLET_HOLDER') : null),
    ],
    'shopeepay' => [
        'category' => 'ewallet',
        'label' => 'ShopeePay',
        'short_label' => 'SPay',
        'recipient' => env('PAYMENT_SHOPEEPAY_RECIPIENT'),
        'account_name' => env('PAYMENT_SHOPEEPAY_ACCOUNT_NAME'),
    ],
    'qris' => [
        'category' => 'qris',
        'label' => 'QRIS',
        'short_label' => 'QRIS',
        'recipient' => null,
        'account_name' => env('PAYMENT_QRIS_ACCOUNT_NAME'),
        'qr_image' => env('PAYMENT_QRIS_IMAGE'),
    ],
];

return [
    'methods' => array_map(
        static function (array $method): array {
            $configured = $method['category'] === 'qris'
                ? filled($method['qr_image'])
                    && is_file(public_path(ltrim($method['qr_image'], '/')))
                    && filled($method['account_name'])
                : filled($method['recipient']) && filled($method['account_name']);

            return [...$method, 'enabled' => $configured];
        },
        $methods,
    ),
    'admin_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('PAYMENT_ADMIN_EMAILS', '')),
    ))),
    'expiry_hours' => max(1, (int) env('PAYMENT_EXPIRY_HOURS', 24)),
    'proof_max_kilobytes' => 5120,
    'proof_disk' => env('PAYMENT_PROOF_DISK', 'supabase'),
];
