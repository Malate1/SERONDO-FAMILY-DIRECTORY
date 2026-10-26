<?php
// Run with: php tests/logout_notification.php
define('BASEPATH', __DIR__ . '/../system/');
class CI_Controller { public $session; public $input; }
require BASEPATH . 'libraries/Session/Session.php';
require __DIR__ . '/../application/controllers/Auth.php';
function redirect($path) { throw new RuntimeException('redirect:' . $path); }

ini_set('session.use_cookies', '0');
ini_set('session.save_path', sys_get_temp_dir());
session_id('family-tree-test-' . bin2hex(random_bytes(8)));
session_start();
$old_id = session_id();
$_SESSION = ['rep_id' => 10, 'rep_clan_id' => 1, 'rep_name' => 'Test Representative', 'private_data' => 'clear me'];
$auth = (new ReflectionClass('Auth'))->newInstanceWithoutConstructor();
$auth->session = (new ReflectionClass('CI_Session'))->newInstanceWithoutConstructor();
$auth->input = new class {
    public function method() { return 'post'; }
};
$failures = [];
try {
    try { $auth->logout(); }
    catch (RuntimeException $exception) {
        if ($exception->getMessage() !== 'redirect:login') { throw $exception; }
    }
    if (session_id() === $old_id) { $failures[] = 'The old session ID was retained.'; }
    foreach (['rep_id', 'rep_clan_id', 'rep_name', 'private_data'] as $key) {
        if (isset($_SESSION[$key])) { $failures[] = 'Session value was retained: ' . $key; }
    }
    if ($auth->session->flashdata('success') !== 'You have been signed out.') { $failures[] = 'Logout notification is missing.'; }
} finally { session_destroy(); }
if ($failures) { fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL); exit(1); }
echo 'Logout clears session data, rotates its ID, and retains the notification.' . PHP_EOL;
