<?php
// Run with: php tests/https_base_url.php
define('BASEPATH', __DIR__);

$cases = [
    ['HTTP site', [], 'http://family.example/family-tree/'],
    ['HTTPS site', ['HTTPS' => 'on'], 'https://family.example/family-tree/'],
    ['HTTPS numeric flag', ['HTTPS' => '1'], 'https://family.example/family-tree/'],
    ['HTTP flag off', ['HTTPS' => 'off'], 'http://family.example/family-tree/'],
    ['HTTPS port', ['SERVER_PORT' => '443'], 'https://family.example/family-tree/'],
    ['HTTPS proxy', ['HTTP_X_FORWARDED_PROTO' => 'https'], 'https://family.example/family-tree/'],
    ['HTTPS proxy chain', ['HTTP_X_FORWARDED_PROTO' => 'https, http'], 'https://family.example/family-tree/'],
    ['HTTP proxy', ['HTTP_X_FORWARDED_PROTO' => 'http'], 'http://family.example/family-tree/'],
];

$failures = 0;
foreach ($cases as $case)
{
    $_SERVER = array_merge([
        'HTTP_HOST' => 'family.example',
        'SCRIPT_NAME' => '/family-tree/index.php',
        'SERVER_PORT' => '80',
    ], $case[1]);
    $config = [];
    require __DIR__ . '/../application/config/config.php';
    if ($config['base_url'] !== $case[2])
    {
        fwrite(STDERR, $case[0] . ': expected ' . $case[2] . ', got ' . $config['base_url'] . PHP_EOL);
        $failures++;
    }
}
if ($failures) { exit(1); }
echo count($cases) . ' URL scheme checks passed.' . PHP_EOL;
