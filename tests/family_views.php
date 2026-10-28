<?php
// Run with: php tests/family_views.php
// Render real views with fixture data, without loading the database or layout.
define('BASEPATH', __DIR__ . '/../system/');
class CI_Model {}
function html_escape($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function site_url($path) { return '/' . $path; }
function base_url($path) { return '/' . $path; }
function current_rep() { return (object) ['id' => 9, 'clan_id' => 1]; }
function form_error($field, $before = '', $after = '') { return ''; }
function validation_errors() { return ''; }
function &get_instance() { return $GLOBALS['view_ci']; }
$GLOBALS['view_ci'] = (object) [
    'config' => new class { public function item($key) { return []; } },
    'security' => new class {
        public function get_csrf_token_name() { return 'csrf'; }
        public function get_csrf_hash() { return 'fixture'; }
    },
];
require __DIR__ . '/../application/helpers/clan_helper.php';
require __DIR__ . '/../application/models/Member_model.php';
class ViewFixture {
    public $load;
    public function __construct() { $this->load = new class { public function view($name, $data = []) {} }; }
    public function render($view, $data) {
        extract($data);
        ob_start();
        require __DIR__ . '/../application/views/' . $view . '.php';
        return ob_get_clean();
    }
}
function assert_view($condition, $message) { if ( ! $condition) { throw new RuntimeException($message); } }
function xpath($html) {
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(TRUE);
    $document->loadHTML('<meta charset="utf-8">' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    return new DOMXPath($document);
}
function class_query($class) { return 'contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")'; }

$members = [];
for ($index = 1; $index <= 25; $index++) {
    $members[] = (object) ['id' => $index, 'clan_id' => $index <= 15 ? 1 : 2,
        'clan_name' => $index <= 15 ? 'First clan' : 'Second clan',
        'first_name' => $index === 2 ? 'Ana <script>alert(1)</script>' : 'Relative ' . $index,
        'last_name' => 'Test', 'parent_id' => $index === 1 || $index === 16 ? NULL : 1,
        'parent_name' => 'Relative 1 Test', 'generation' => $index === 1 || $index === 16 ? 1 : 2,
        'birth_date' => '2000-' . date('m-d'), 'is_deceased' => 0,
        'death_date' => NULL, 'photo' => NULL, 'created_at' => '2020-01-01'];
}
$clans = [(object) ['id' => 1, 'clan_name' => 'First clan', 'member_count' => 15, 'max_generation' => 2],
    (object) ['id' => 2, 'clan_name' => 'Second clan', 'member_count' => 10, 'max_generation' => 2]];
$renderer = new ViewFixture();
$rendered = [];
$rendered['dashboard'] = $renderer->render('dashboard', [
    'members' => $members, 'clans' => $clans, 'spouses' => [], 'generation_labels' => [], 'upcoming_events' => [],
]);
$directory = xpath($rendered['dashboard']);
assert_view($directory->query('//*[@id="directory"]//a[' . class_query('member-card') . ']')->length === 25, 'Search must retain every member.');
assert_view($directory->query('//a[' . class_query('member-card') . ' and not(' . class_query('hidden') . ')]')->length === 12, 'Initially show only 12 relatives.');
assert_view($directory->query('//*[@id="tabs"]//*[@aria-pressed="true"]')->length === 1, 'One clan filter must be active.');
assert_view($directory->query('//a[' . class_query('member-card') . ' and starts-with(@href, "/clan_members/member/")]')->length === 25, 'Directory links must open focused family pages.');
assert_view(strpos($rendered['dashboard'], 'id="directory"') < strpos($rendered['dashboard'], 'id="upcoming-events-title"'), 'Browsing must come before family updates.');
assert_view(strpos($rendered['dashboard'], '<script>alert(1)</script>') === FALSE, 'Member names must be escaped.');
$empty = xpath($renderer->render('dashboard', ['members' => [], 'clans' => [], 'spouses' => [], 'generation_labels' => [], 'upcoming_events' => []]));
assert_view($empty->query('//*[@id="show-more-members" and @hidden]')->length === 1, 'An empty directory must hide Show more.');

$family_data = ['title' => 'Family', 'member' => $members[0], 'clan' => $clans[0],
    'children' => [$members[1]], 'spouses' => [], 'can_manage' => TRUE, 'max_generation' => 6];
$rendered['member'] = $renderer->render('member_view', $family_data);
$family = xpath($rendered['member']);
assert_view($family->query('//a[@href="/clan_members/children/1"]')->length === 1, 'The child action must target this parent.');
assert_view($family->query('//*[@id="children"]//a[@href="/clan_members/member/2"]')->length === 1, 'Children must open their own families.');
assert_view($family->query('//form[contains(@action, "/delete/")]')->length === 0, 'Parents must not have a delete action.');
$family_data['can_manage'] = FALSE;
$public = xpath($renderer->render('member_view', $family_data));
assert_view($public->query('//a[contains(@href, "/children/") or contains(@href, "/edit/") or contains(@href, "/spouses/")]')->length === 0, 'Public pages must hide management actions.');
$family_data['can_manage'] = TRUE;
$family_data['children'] = [];
$family_data['member'] = clone $members[1];
$family_data['member']->generation = 6;
$leaf = xpath($renderer->render('member_view', $family_data));
assert_view($leaf->query('//a[contains(@href, "/children/")]')->length === 0, 'Maximum generation must not allow adding children.');
assert_view($leaf->query('//form[@action="/clan_members/delete/2"]')->length === 1, 'Leaf deletion must remain available.');
assert_view($leaf->query('//a[@href="/clan_members/member/1"]')->length === 1, 'A child must link back to their parent.');

$rendered['children'] = $renderer->render('manage_children', ['title' => 'Add children', 'parent' => $members[0],
    'clan' => $clans[0], 'generation' => 2, 'children' => [$members[1]], 'rows' => [
        ['first_name' => '', 'last_name' => '', 'birth_date' => '']], 'errors' => [], 'form_error' => NULL]);
$child_form = xpath($rendered['children']);
assert_view($child_form->query('//form[@action="/clan_members/children/1"]')->length === 1, 'The child form must post to this parent.');
assert_view($child_form->query('//a[@href="/clan_members/member/1" and contains(., "Back to parent")]')->length === 1, 'Back must return to the selected parent.');

$model = (new ReflectionClass('Member_model'))->newInstanceWithoutConstructor();
$clan_members = array_slice($members, 0, 15);
$rendered['clan'] = $renderer->render('clan_view', ['members' => $clan_members, 'clan' => $clans[0],
    'stats' => (object) ['member_count' => 15, 'max_generation' => 2, 'per_generation' => [1 => 1, 2 => 14]],
    'tree' => $model->build_tree($clan_members), 'spouses' => [], 'can_manage' => TRUE, 'max_generation' => 6]);
$clan = xpath($rendered['clan']);
assert_view($clan->query('//*[@id="grid-view"]//a[contains(., "Open family")]')->length === 15, 'Each clan card must offer a focused family page.');
assert_view($clan->query('//*[@id="tree-view"]//a[contains(., "Open family")]')->length === 15, 'Each tree node must offer a focused family page.');
foreach ($rendered as $view => $html) {
    $ids = [];
    foreach (xpath($html)->query('//*[@id]') as $element) {
        $id = $element->getAttribute('id');
        assert_view( ! isset($ids[$id]), $view . ' has a duplicate ID: ' . $id);
        $ids[$id] = TRUE;
    }
}

if (in_array('--scripts', $argv, TRUE)) {
    foreach ($rendered as $html) {
        preg_match_all('/<script>(.*?)<\/script>/s', $html, $scripts);
        foreach ($scripts[1] as $script) { echo $script . PHP_EOL; }
    }
} elseif (in_array('--directory-fixture', $argv, TRUE)) {
    $script = substr($rendered['dashboard'], strpos($rendered['dashboard'], "    var search = document.getElementById('search');"));
    $script = substr($script, 0, strpos($script, '})();'));
    $cards = [];
    foreach ($directory->query('//a[' . class_query('member-card') . ']') as $card) {
        $cards[] = ['name' => $card->getAttribute('data-name'), 'clan' => $card->getAttribute('data-clan'),
            'hidden' => strpos($card->getAttribute('class'), 'hidden') !== FALSE];
    }
    echo json_encode(['script' => $script, 'cards' => $cards]);
} else {
    echo 'Dashboard, parent pages, clan views, child links, permissions, and empty states rendered correctly.' . PHP_EOL;
}
