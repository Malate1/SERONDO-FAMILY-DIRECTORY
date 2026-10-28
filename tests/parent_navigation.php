<?php
// Run with: php tests/parent_navigation.php
// Controller regressions use in-memory models; no family records are changed.
define('BASEPATH', __DIR__ . '/../system/');
class CI_Controller { public $load, $input, $session, $Member_model, $Clan_model; }
require __DIR__ . '/../application/controllers/Clan_members.php';

function current_rep() { return $GLOBALS['test_rep']; }
function member_full_name($member) { return trim($member->first_name . ' ' . $member->last_name); }
function redirect($path) { throw new RuntimeException('redirect:' . $path); }
function show_404() { throw new RuntimeException('http:404'); }
function show_error($message, $status, $heading = '') { throw new RuntimeException('http:' . $status); }
function check($condition, $message) {
    if ( ! $condition) { throw new RuntimeException($message); }
}
function expect_result($expected, $action) {
    try { $action(); }
    catch (RuntimeException $error) {
        check($error->getMessage() === $expected, 'Expected ' . $expected . ', got ' . $error->getMessage());
        return;
    }
    throw new RuntimeException('Expected ' . $expected . ', but no response was returned.');
}

function controller($post = NULL, $clan_id = 1, $generation = 2) {
    $GLOBALS['test_rep'] = (object) ['id' => 9, 'clan_id' => 1, 'is_active' => 1];
    $controller = (new ReflectionClass('Clan_members'))->newInstanceWithoutConstructor();
    $maximum = new ReflectionProperty('Clan_members', 'max_generation');
    $maximum->setAccessible(TRUE);
    $maximum->setValue($controller, 6);
    $parent = (object) ['id' => 42, 'clan_id' => $clan_id, 'generation' => $generation,
        'first_name' => 'Maria', 'last_name' => 'Test', 'parent_id' => 1];
    $controller->Member_model = new class($parent) {
        public $parent, $saved = [], $save_ok = TRUE;
        public function __construct($parent) { $this->parent = $parent; }
        public function get($id) { return (int) $id === 42 ? $this->parent : NULL; }
        public function get_children($id) { return [(object) ['id' => 77, 'parent_id' => $id]]; }
        public function get_spouses($id) { return []; }
        public function add_children($parent, $clan, $rep, $generation, $children) {
            $this->saved = compact('parent', 'clan', 'rep', 'generation', 'children');
            return $this->save_ok;
        }
    };
    $controller->Clan_model = new class {
        public function get($id) { return (object) ['id' => $id, 'clan_name' => 'Test clan']; }
        public function get_representative($id) { return current_rep(); }
    };
    $controller->input = new class($post) {
        private $data;
        public function __construct($data) { $this->data = $data; }
        public function method() { return $this->data === NULL ? 'get' : 'post'; }
        public function post($key, $filter = FALSE) { return isset($this->data[$key]) ? $this->data[$key] : NULL; }
    };
    $controller->session = new class {
        public $messages = [];
        public function set_flashdata($key, $message) { $this->messages[$key] = $message; }
    };
    $controller->load = new class {
        public $name, $data;
        public function view($name, $data) { $this->name = $name; $this->data = $data; }
    };
    return $controller;
}

$controller = controller();
$GLOBALS['test_rep'] = NULL;
$controller->member(42);
check($controller->load->name === 'member_view', 'Opening a parent must render the focused family page.');
check($controller->load->data['children'][0]->parent_id === 42, 'Only the selected parent\'s children should be loaded.');
check( ! $controller->load->data['can_manage'], 'Public visitors must not get management actions.');
foreach ([NULL, 'bad-id', 999] as $id) {
    expect_result('http:404', function () use ($controller, $id) { $controller->member($id); });
}
$controller = controller(NULL, 2);
$controller->member(42);
check( ! $controller->load->data['can_manage'], 'Another clan\'s representative must not get management actions.');
expect_result('http:403', function () use ($controller) { $controller->children(42); });
$controller = controller();
$GLOBALS['test_rep'] = NULL;
expect_result('redirect:login', function () use ($controller) { $controller->children(42); });

$controller = controller();
$controller->children(42);
check($controller->load->data['parent']->id === 42, 'The child form must keep the selected parent.');
check($controller->load->data['generation'] === 3, 'Children must use their parent\'s next generation.');

$post = ['first_name' => ['Ana', 'Luis'], 'last_name' => ['Test', 'Test'], 'birth_date' => ['', '2005-03-10'],
    'parent_id' => 999, 'clan_id' => 2, 'generation' => 6];
$controller = controller($post);
expect_result('redirect:clan_members/member/42#children', function () use ($controller) { $controller->children(42); });
$saved = $controller->Member_model->saved;
check($saved['parent'] === 42 && $saved['clan'] === 1 && $saved['generation'] === 3 && $saved['rep'] === 9,
    'Posted parent/clan/generation fields must never override the selected parent and representative.');
check(count($saved['children']) === 2 && $saved['children'][0]['birth_date'] === NULL, 'Both children and optional birth dates must be saved.');

$controller = controller(['first_name' => ['A'], 'last_name' => ['Test'], 'birth_date' => ['']]);
$controller->children(42);
check(isset($controller->load->data['errors'][0]['first_name']), 'Invalid children must display their validation errors.');
check($controller->load->data['parent']->id === 42 && ! $controller->Member_model->saved, 'Validation errors must retain the parent without saving.');
$controller = controller($post);
$controller->Member_model->save_ok = FALSE;
$controller->children(42);
check($controller->load->data['form_error'] !== NULL && $controller->load->data['parent']->id === 42,
    'A failed save must retain the form and selected parent.');
$controller = controller(NULL, 1, 6);
expect_result('redirect:clan_members/member/42', function () use ($controller) { $controller->children(42); });

echo 'Parent pages, child save destinations, validation, and clan permissions passed.' . PHP_EOL;
