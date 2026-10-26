<?php defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('layout/header', ['title' => isset($title) ? $title : 'Reunion Directory']);
$rep   = current_rep();
$total = count($members);
$today_month_day = date('m-d');
$current_month = date('m');
$today_day = date('d');
$birthdays_this_month = [];
foreach ($members as $member)
{
    if (empty($member->birth_date) || (isset($member->is_deceased) && (int) $member->is_deceased === 1))
    {
        continue;
    }
    $birthday_month_day = substr($member->birth_date, 5, 5);
    if (substr($birthday_month_day, 0, 2) === $current_month && substr($birthday_month_day, 3, 2) >= $today_day)
    {
        $member->birthday_month_day = $birthday_month_day;
        $birthdays_this_month[] = $member;
    }
}
usort($birthdays_this_month, function ($a, $b) {
    return strcmp($a->birthday_month_day, $b->birthday_month_day);
});
$tree_members = [];
foreach ($members as $member)
{
    $spouse_names = [];
    if (isset($spouses[(int) $member->id]))
    {
        foreach ($spouses[(int) $member->id] as $spouse)
        {
            $spouse_names[] = member_full_name($spouse);
        }
    }
    $tree_members[(int) $member->clan_id][] = [
        'id'         => (int) $member->id,
        'clan_id'    => (int) $member->clan_id,
        'parent_id'  => $member->parent_id === NULL ? NULL : (int) $member->parent_id,
        'generation' => (int) $member->generation,
        'name'       => member_full_name($member),
        'clan_name'  => $member->clan_name,
        'spouses'    => $spouse_names,
    ];
}
?>

<!-- Hero -->
<section class="mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 p-6 shadow-xl sm:p-10">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-indigo-300">Family Reunion</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-white sm:text-4xl">Four clans. One family.</h1>
            <p class="mt-2 max-w-xl text-slate-300">Find your relatives, trace your branch back to the clan ancestor, and see who's coming to the reunion.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <?php if ($total > 0): ?>
                <button type="button" id="generate-full-tree" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-lg transition hover:-translate-y-0.5 hover:bg-slate-100">
                    Generate full family tree
                </button>
            <?php endif; ?>
            <?php if ($rep): ?>
                <a href="<?= site_url('clan_members/add') ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-500 px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-indigo-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    Add a member to my clan
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Upcoming birthdays -->
<section aria-labelledby="birthdays-title" class="mb-12 rounded-3xl border border-sky-100 bg-gradient-to-br from-sky-50 to-white p-6 shadow-sm sm:p-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-sky-700">Celebrate together</p>
            <h2 id="birthdays-title" class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Upcoming birthdays this month</h2>
            <p class="mt-1 text-sm text-slate-600">Based on recorded birth dates. Today’s birthdays appear first.</p>
        </div>
        <a href="<?= site_url('memorials') ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
            Visit In Memoriam
        </a>
    </div>
    <?php if ($birthdays_this_month): ?>
        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($birthdays_this_month as $birthday):
                $birthday_date = date('M j', strtotime($birthday->birth_date));
                $is_today = $birthday->birthday_month_day === $today_month_day;
                $years_old = member_age($birthday->birth_date);
                if ( ! $is_today && $years_old !== NULL)
                {
                    $years_old++;
                }
            ?>
                <a href="<?= site_url('clan_members/clan/' . $birthday->clan_id) ?>#m-<?= (int) $birthday->id ?>" class="grid grid-cols-[3rem_minmax(0,1fr)] items-center gap-x-3 gap-y-2 rounded-2xl border border-white bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <?= member_avatar($birthday, 'h-12 w-12', 'text-sm') ?>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-bold text-slate-900"><?= html_escape(member_full_name($birthday)) ?></span>
                        <span class="mt-1 block text-xs text-slate-500"><?= $is_today ? 'Today' : html_escape($birthday_date) ?><?= $years_old !== NULL ? ' · ' . ($is_today ? 'turns ' : 'turning ') . $years_old : '' ?></span>
                    </span>
                    <span class="col-start-2 justify-self-start rounded-2xl bg-sky-100 px-2 py-1 text-[11px] font-semibold text-sky-700"><?= html_escape(preg_replace('/\s*\(.*\)$/', '', $birthday->clan_name)) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="mt-5 rounded-2xl border border-dashed border-sky-200 bg-white/70 px-4 py-6 text-center text-sm text-slate-500">No upcoming birthdays recorded for the rest of this month.</p>
    <?php endif; ?>
</section>

<!-- Upcoming family events -->
<section aria-labelledby="upcoming-events-title" class="mb-12 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Stay connected</p>
            <h2 id="upcoming-events-title" class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Upcoming family events</h2>
        </div>
        <a href="<?= site_url('events') ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
            Open clan calendar
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>
    <?php if ($upcoming_events): ?>
        <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2">
            <?php foreach ($upcoming_events as $event):
                $event_colors = ['reunion' => 'bg-rose-500', 'celebration' => 'bg-sky-500', 'memorial' => 'bg-slate-700'];
                $event_date = new DateTime($event->starts_at);
            ?>
                <a href="<?= site_url('events') ?>#event-<?= (int) $event->id ?>" class="grid grid-cols-[0.75rem_minmax(0,1fr)] items-start gap-x-3 gap-y-2 rounded-2xl border border-slate-100 p-4 transition hover:border-slate-300 hover:bg-slate-50">
                    <span class="mt-1 h-3 w-3 rounded-full <?= isset($event_colors[$event->category]) ? $event_colors[$event->category] : 'bg-indigo-500' ?>"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-bold text-slate-900"><?= html_escape($event->title) ?></span>
                        <span class="mt-1 block text-xs text-slate-500"><?= html_escape($event_date->format('M j, Y · g:i A')) ?> · <?= $event->clan_id === NULL ? 'All clans' : html_escape($event->scope_clan_name) ?></span>
                    </span>
                    <span class="col-start-2 text-xs font-semibold text-slate-600"><?= (int) $event->total_headcount ?> expected</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="mt-4 text-sm text-slate-500">No upcoming events are scheduled yet. Visit the calendar to see past events or create one as a representative.</p>
    <?php endif; ?>
</section>

<!-- Clan summary cards -->
<section aria-label="Clans" class="mb-12 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <?php foreach ($clans as $clan): $t = clan_theme($clan->id); $rec = isset($recent[(int) $clan->id]) ? $recent[(int) $clan->id] : []; ?>
        <article class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl <?= $t['hover'] ?>">
            <div class="bg-gradient-to-br px-5 py-5 <?= $t['gradient'] ?>">
                <h2 class="text-lg font-bold leading-tight text-white"><?= html_escape($clan->clan_name) ?></h2>
                <div class="mt-4 flex items-end gap-5 text-white">
                    <div>
                        <p class="text-3xl font-bold leading-none"><?= (int) $clan->member_count ?></p>
                        <p class="mt-1 text-xs font-medium uppercase tracking-wider text-white/75">Members</p>
                    </div>
                    <div>
                        <p class="text-3xl font-bold leading-none"><?= (int) $clan->max_generation ?></p>
                        <p class="mt-1 text-xs font-medium uppercase tracking-wider text-white/75">Generations</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-1 flex-col p-5">
                <?php if ( ! empty($clan->description)): ?>
                    <p class="mb-4 line-clamp-2 text-sm text-slate-500"><?= html_escape($clan->description) ?></p>
                <?php endif; ?>

                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Recent additions</h3>
                <?php if ($rec): ?>
                    <ul class="space-y-2.5">
                        <?php foreach ($rec as $r): ?>
                            <li>
                                <a href="<?= site_url('clan_members/clan/' . $clan->id) ?>#m-<?= (int) $r->id ?>" class="flex items-center gap-3 rounded-lg p-1 transition hover:bg-slate-50">
                                    <?= member_avatar($r, 'h-9 w-9', 'text-xs') ?>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-slate-800"><?= html_escape(member_full_name($r)) ?></span>
                                        <span class="block text-xs text-slate-400">Gen <?= (int) $r->generation ?> · added <?= html_escape(date('M j', strtotime($r->created_at))) ?></span>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-sm text-slate-400">No members yet.</p>
                <?php endif; ?>

                <div class="mt-5 flex gap-2">
                    <a href="<?= site_url('clan_members/clan/' . $clan->id) ?>"
                       class="inline-flex flex-1 items-center justify-center gap-1 rounded-xl px-3 py-2.5 text-center text-xs font-semibold text-white shadow-md transition <?= $t['button'] ?>">
                        Open Branch
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <?php if ((int) $clan->member_count > 0): ?>
                        <button type="button" data-generate-tree="<?= (int) $clan->id ?>" data-clan-name="<?= html_escape($clan->clan_name) ?>"
                                class="inline-flex flex-1 items-center justify-center gap-1 rounded-xl bg-slate-100 px-3 py-2.5 text-center text-xs font-semibold text-slate-700 transition hover:bg-slate-200">
                            Generate tree
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16M8 3v6m8 6v6"/></svg>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<!-- Generated family tree -->
<dialog id="family-tree-modal" class="app-dialog" aria-labelledby="family-tree-title">
    <section class="dialog-panel flex w-full max-w-7xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
        <header class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-7">
            <div class="min-w-0">
                <h2 id="family-tree-title" class="text-xl font-bold text-slate-900">Family tree</h2>
                <p id="family-tree-help" class="mt-1 text-sm text-slate-500">Scroll across and down to explore the connected generations.</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <button type="button" id="print-family-tree" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">Generate PDF Chart</button>
                <button type="button" id="close-family-tree" aria-label="Close family tree" class="shrink-0 rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>
        </header>
        <div id="family-tree-scroll" tabindex="0" role="region" aria-label="Family tree chart" aria-describedby="family-tree-help" class="min-h-0 overflow-auto overscroll-contain bg-slate-50 p-4 sm:p-6">
            <div id="family-tree-chart" class="relative"></div>
        </div>
    </section>
</dialog>

<!-- Relationship calculator -->
<section aria-labelledby="relationship-title" class="mb-12 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
    <div class="max-w-2xl">
        <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Family connection</p>
        <h2 id="relationship-title" class="mt-1 text-2xl font-bold tracking-tight text-slate-900">How are we related?</h2>
        <p class="mt-2 text-sm text-slate-500">Choose two relatives to find their closest recorded common ancestor.</p>
    </div>
    <form id="relationship-form" data-submit-mode="client" data-confirm-title="Find this relationship?" data-confirm-text="Compare the selected relatives using the recorded family tree?" data-confirm-fields="relative-one relative-two" data-confirm-button="Find relationship" class="mt-6 grid grid-cols-1 items-end gap-4 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)_auto]">
        <div>
            <label for="relative-one" class="mb-1.5 block text-sm font-semibold text-slate-700">First relative</label>
            <select id="relative-one" required class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15">
                <option value="">Choose a family member</option>
                <?php foreach ($members as $member): ?>
                    <option value="<?= (int) $member->id ?>"><?= html_escape(member_full_name($member) . ' · ' . $member->clan_name . ' · Gen ' . (int) $member->generation) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <span class="hidden pb-3 text-center text-sm font-semibold text-slate-400 lg:block">and</span>
        <div>
            <label for="relative-two" class="mb-1.5 block text-sm font-semibold text-slate-700">Second relative</label>
            <select id="relative-two" required class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15">
                <option value="">Choose a family member</option>
                <?php foreach ($members as $member): ?>
                    <option value="<?= (int) $member->id ?>"><?= html_escape(member_full_name($member) . ' · ' . $member->clan_name . ' · Gen ' . (int) $member->generation) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500/20">Find relationship</button>
    </form>
    <div id="relationship-result" class="mt-5 hidden rounded-2xl border border-indigo-100 bg-indigo-50 px-5 py-4 text-sm text-indigo-950" role="status" aria-live="polite"></div>
</section>

<!-- Directory: filter + instant search across all branches -->
<section aria-label="All members">
    <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-xl font-bold tracking-tight">Find a relative</h2>
            <span id="count" class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm ring-1 ring-slate-200"><?= $total ?> people</span>
        </div>
        <div class="relative w-full lg:w-96">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/></svg>
            <label for="search" class="sr-only">Search by name across all clans</label>
            <input id="search" type="search" autocomplete="off" placeholder="Search by name across all clans…"
                   class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-4 text-sm shadow-sm placeholder:text-slate-400 transition focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15">
        </div>
    </div>

    <!-- Clan filter tabs -->
    <div id="tabs" class="mb-6 flex flex-wrap gap-2" role="tablist">
        <button type="button" role="tab" data-clan="all" class="tab inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition">All clans</button>
        <?php foreach ($clans as $clan): $t = clan_theme($clan->id); ?>
            <button type="button" role="tab" data-clan="<?= (int) $clan->id ?>"
                    class="tab inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition">
                <span class="h-2.5 w-2.5 rounded-full <?= $t['solid'] ?>"></span>
                <?= html_escape(preg_replace('/\s*\(.*\)$/', '', $clan->clan_name)) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <?php if ($total === 0): ?>
        <div class="rounded-3xl border-2 border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <p class="text-lg font-bold text-slate-800">No members yet</p>
            <p class="mt-1 text-sm text-slate-500">Clan representatives can start adding relatives after signing in.</p>
        </div>
    <?php else: ?>
        <div id="grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <?php foreach ($members as $m):
                $t = clan_theme($m->clan_id);
                $deceased = isset($m->is_deceased) && (int) $m->is_deceased === 1;
                $age = $deceased ? NULL : member_age($m->birth_date);
            ?>
                <a href="<?= site_url('clan_members/clan/' . $m->clan_id) ?>#m-<?= (int) $m->id ?>"
                   class="member-card flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-lg <?= $t['hover'] ?>"
                   data-clan="<?= (int) $m->clan_id ?>"
                   data-name="<?= html_escape(mb_strtolower(member_full_name($m) . ' ' . $m->clan_name)) ?>">
                    <?= member_avatar($m, 'h-14 w-14', 'text-lg') ?>
                    <div class="min-w-0 flex-1">
                        <p class="mobile-wrap truncate text-sm font-bold text-slate-900"><?= html_escape(member_full_name($m)) ?></p>
                        <p class="truncate text-xs text-slate-500"><?= html_escape(member_birth_label($m->birth_date)) ?><?= $age !== NULL ? ' · ' . $age . ' yrs' : '' ?></p>
                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 <?= $t['soft'] ?>"><?= html_escape(preg_replace('/\s*\(.*\)$/', '', $m->clan_name)) ?></span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">Gen <?= (int) $m->generation ?></span>
                            <?php if ($deceased): ?><span class="rounded-full bg-slate-200 px-2 py-0.5 text-[11px] font-semibold text-slate-700">In memoriam</span><?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div id="no-results" class="hidden rounded-3xl border border-slate-200 bg-white px-6 py-14 text-center shadow-sm">
            <p class="font-bold text-slate-800">No matching relatives</p>
            <p class="mt-1 text-sm text-slate-500">Check the spelling, or switch back to “All clans”.</p>
        </div>
    <?php endif; ?>
</section>

<script>
(function () {
    var relatives = <?= json_encode(array_map(function ($member) {
        return [
            'id'         => (int) $member->id,
            'parent_id'  => $member->parent_id === NULL ? NULL : (int) $member->parent_id,
            'generation' => (int) $member->generation,
            'name'       => member_full_name($member),
        ];
    }, $members), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var relativesById = {};
    relatives.forEach(function (relative) { relativesById[relative.id] = relative; });

    var relationshipForm = document.getElementById('relationship-form');
    var relationshipResult = document.getElementById('relationship-result');
    var relativeOne = document.getElementById('relative-one');
    var relativeTwo = document.getElementById('relative-two');

    function ancestorPath(memberId) {
        var path = [];
        var visited = {};
        var current = relativesById[memberId];
        while (current && !visited[current.id]) {
            path.push(current);
            visited[current.id] = true;
            current = current.parent_id === null ? null : relativesById[current.parent_id];
        }
        return path;
    }

    function ordinal(number) {
        var words = ['', 'first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth'];
        if (number < words.length) { return words[number]; }
        var remainder = number % 100;
        if (remainder >= 11 && remainder <= 13) { return number + 'th'; }
        switch (number % 10) {
            case 1: return number + 'st';
            case 2: return number + 'nd';
            case 3: return number + 'rd';
            default: return number + 'th';
        }
    }

    function ancestorTerm(distance) {
        if (distance === 1) { return 'parent'; }
        if (distance === 2) { return 'grandparent'; }
        return 'great-'.repeat(distance - 2) + 'grandparent';
    }

    function describeRelationship(first, second, firstDistance, secondDistance) {
        if (firstDistance === 0) {
            return first.name + ' is ' + second.name + "'s " + ancestorTerm(secondDistance) + '.';
        }
        if (secondDistance === 0) {
            return second.name + ' is ' + first.name + "'s " + ancestorTerm(firstDistance) + '.';
        }
        if (firstDistance === 1 && secondDistance === 1) {
            return first.name + ' and ' + second.name + ' are siblings.';
        }
        if (firstDistance === 1 || secondDistance === 1) {
            var older = firstDistance === 1 ? first : second;
            var younger = firstDistance === 1 ? second : first;
            var youngerDistance = Math.max(firstDistance, secondDistance);
            var removed = youngerDistance - 1;
            var direction = firstDistance === 1 ? 'older than' : 'younger than';
            return older.name + ' is ' + younger.name + "'s aunt/uncle, " + direction + ' them by ' + removed + (removed === 1 ? ' generation.' : ' generations.');
        }

        var degree = Math.min(firstDistance, secondDistance) - 1;
        var removedCount = Math.abs(firstDistance - secondDistance);
        var cousinTerm = ordinal(degree) + ' cousins';
        if (removedCount) {
            cousinTerm += ' ' + removedCount + (removedCount === 1 ? ' time' : ' times') + ' removed';
        }
        return first.name + ' and ' + second.name + ' are ' + cousinTerm + '.';
    }

    function showRelationship(message, type) {
        relationshipResult.classList.remove('hidden');
        relationshipResult.textContent = message;
        if (window.AppUI) { window.AppUI.notify(type, message); }
    }

    function findRelationship(event) {
        event.preventDefault();
        if (event.type === 'submit' && window.AppUI && window.AppUI.formsReady) { return; }
        var first = relativesById[relativeOne.value];
        var second = relativesById[relativeTwo.value];
        relationshipResult.classList.remove('hidden');

        if (!first || !second) {
            showRelationship('Select two family members to find their relationship.', 'error');
            return;
        }
        if (first.id === second.id) {
            showRelationship('Please choose two different family members.', 'warning');
            return;
        }

        var firstPath = ancestorPath(first.id);
        var secondPath = ancestorPath(second.id);
        var secondDistances = {};
        secondPath.forEach(function (ancestor, distance) { secondDistances[ancestor.id] = distance; });
        var sharedAncestor = null;
        var firstDistance = 0;
        var secondDistance = 0;
        firstPath.some(function (ancestor, distance) {
            if (Object.prototype.hasOwnProperty.call(secondDistances, ancestor.id)) {
                sharedAncestor = ancestor;
                firstDistance = distance;
                secondDistance = secondDistances[ancestor.id];
                return true;
            }
            return false;
        });

        if (!sharedAncestor) {
            showRelationship('No common ancestor is recorded for ' + first.name + ' and ' + second.name + '. The family tree only includes recorded parent-child links.', 'info');
            return;
        }
        showRelationship(describeRelationship(first, second, firstDistance, secondDistance) +
            ' Their closest shared ancestor is ' + sharedAncestor.name + ' (Generation ' + sharedAncestor.generation + ').', 'success');
    }
    relationshipForm.addEventListener('submit', findRelationship);
    relationshipForm.addEventListener('app:confirmed-submit', findRelationship);

    var membersByClan = <?= json_encode($tree_members, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var treeModal = document.getElementById('family-tree-modal');
    var treeTitle = document.getElementById('family-tree-title');
    var treeChart = document.getElementById('family-tree-chart');
    var closeTree = document.getElementById('close-family-tree');
    var treeScroll = document.getElementById('family-tree-scroll');
    var generationLabels = <?= json_encode($generation_labels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var clanColors = ['#4f46e5', '#059669', '#d97706', '#e11d48'];

    function makeElement(tag, className, text) {
        var element = document.createElement(tag);
        if (className) { element.className = className; }
        if (text !== undefined) { element.textContent = text; }
        return element;
    }

    function generateTree(clanId) {
        var members = clanId === 'all'
            ? Object.keys(membersByClan).reduce(function (allMembers, key) { return allMembers.concat(membersByClan[key]); }, [])
            : (membersByClan[clanId] || []);
        var nodes = {};
        var roots = [];
        var nextColumn = 0;
        var maxGeneration = 0;
        var cardWidth = 190;
        var cardHeight = 76;
        var columnGap = 48;
        var rowGap = 104;
        var leftPadding = 24;
        var topPadding = 66;

        members.forEach(function (member) {
            nodes[member.id] = {
                member: member,
                children: [],
                x: 0,
                y: 0
            };
            maxGeneration = Math.max(maxGeneration, member.generation);
        });
        members.forEach(function (member) {
            var node = nodes[member.id];
            var parent = member.parent_id === null ? null : nodes[member.parent_id];
            if (parent && parent.member.generation < member.generation) {
                parent.children.push(node);
            } else {
                roots.push(node);
            }
        });

        function position(node) {
            if (node.children.length === 0) {
                node.x = leftPadding + nextColumn * (cardWidth + columnGap);
                nextColumn++;
            } else {
                node.children.forEach(position);
                node.x = node.children.reduce(function (sum, child) { return sum + child.x; }, 0) / node.children.length;
            }
            node.y = topPadding + (node.member.generation - 1) * (cardHeight + rowGap);
        }
        roots.forEach(position);

        var width = Math.max(300, nextColumn * (cardWidth + columnGap) - columnGap + leftPadding * 2);
        var height = Math.max(170, maxGeneration * (cardHeight + rowGap) + topPadding);
        treeChart.textContent = '';
        treeChart.style.width = width + 'px';
        treeChart.style.height = height + 'px';

        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('width', width);
        svg.setAttribute('height', height);
        svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);
        svg.setAttribute('aria-hidden', 'true');
        svg.classList.add('pointer-events-none', 'absolute', 'inset-0');

        Object.keys(nodes).forEach(function (id) {
            var node = nodes[id];
            if (node.children.length === 0) { return; }
            var branchY = node.y + cardHeight + rowGap / 2;
            var parentX = node.x + cardWidth / 2;
            var childCenters = node.children.map(function (child) { return child.x + cardWidth / 2; });
            var pathData = 'M ' + parentX + ' ' + (node.y + cardHeight) + ' V ' + branchY;
            if (childCenters.length > 1) {
                pathData += ' M ' + Math.min.apply(Math, childCenters) + ' ' + branchY + ' H ' + Math.max.apply(Math, childCenters);
            }
            node.children.forEach(function (child) {
                pathData += ' M ' + (child.x + cardWidth / 2) + ' ' + branchY + ' V ' + child.y;
            });

            var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', pathData);
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke', clanColors[(node.member.clan_id - 1) % clanColors.length]);
            path.setAttribute('stroke-width', '2');
            svg.appendChild(path);
        });
        treeChart.appendChild(svg);

        for (var generation = 1; generation <= maxGeneration; generation++) {
            var label = generation === 1 ? 'Four siblings' : (generationLabels[generation] || '');
            var heading = makeElement('div', 'absolute left-0 text-xs font-bold uppercase tracking-wider text-slate-500', 'Generation ' + generation + (label ? ' · ' + label : ''));
            heading.style.top = (topPadding + (generation - 1) * (cardHeight + rowGap) - 24) + 'px';
            treeChart.appendChild(heading);
        }
        Object.keys(nodes).forEach(function (id) {
            var node = nodes[id];
            var card = makeElement('article', 'absolute flex h-[76px] w-[190px] flex-col justify-center rounded-xl border border-slate-300 border-t-4 border-t-cyan-500 bg-cyan-50 px-3 shadow-sm');
            card.style.left = node.x + 'px';
            card.style.top = node.y + 'px';
            card.style.borderTopColor = clanColors[(node.member.clan_id - 1) % clanColors.length];
            card.appendChild(makeElement('h3', 'truncate text-sm font-bold text-slate-900', node.member.name));
            var stage = generationLabels[node.member.generation] || ('Generation ' + node.member.generation);
            card.appendChild(makeElement('p', 'mt-1 truncate text-xs text-slate-600', node.member.clan_name + ' · ' + stage));
            if (node.member.spouses.length) {
                card.appendChild(makeElement('p', 'mt-1 truncate text-xs text-indigo-700', 'Spouse: ' + node.member.spouses.join(', ')));
            }
            treeChart.appendChild(card);
        });
    }

    function openTree(clanId, title) {
        generateTree(clanId);
        treeTitle.textContent = title;
        treeScroll.scrollTop = 0;
        treeScroll.scrollLeft = 0;
        treeModal.showModal();
        document.body.classList.add('dialog-open');
        closeTree.focus();
    }

    function hideTree() {
        treeModal.close();
    }
    treeModal.addEventListener('close', function () { document.body.classList.remove('dialog-open'); });

    Array.prototype.forEach.call(document.querySelectorAll('[data-generate-tree]'), function (button) {
        button.addEventListener('click', function () {
            var clanId = button.getAttribute('data-generate-tree');
            openTree(clanId, button.getAttribute('data-clan-name') + ' family tree');
        });
    });

    var fullTreeButton = document.getElementById('generate-full-tree');
    if (fullTreeButton) {
        fullTreeButton.addEventListener('click', function () {
            openTree('all', 'Full family tree across all clans');
        });
    }

    var printTreeButton = document.getElementById('print-family-tree');
    printTreeButton.addEventListener('click', function () {
        var previousTitle = document.title;
        var restorePrintState = function () {
            document.body.classList.remove('print-full-family-tree');
            document.title = previousTitle;
            window.removeEventListener('afterprint', restorePrintState);
        };
        document.body.classList.add('print-full-family-tree');
        document.title = 'Full_Clan_Family_Tree';
        window.addEventListener('afterprint', restorePrintState);
        window.print();
    });
    closeTree.addEventListener('click', hideTree);
    treeModal.addEventListener('click', function (event) {
        if (event.target === treeModal) { hideTree(); }
    });

    var search = document.getElementById('search');
    var cards  = Array.prototype.slice.call(document.querySelectorAll('.member-card'));
    var tabs   = Array.prototype.slice.call(document.querySelectorAll('#tabs .tab'));
    var count  = document.getElementById('count');
    var none   = document.getElementById('no-results');
    var clan   = 'all';

    var ON  = ['bg-slate-900', 'text-white'];
    var OFF = ['bg-white', 'text-slate-700', 'ring-1', 'ring-slate-200'];

    function apply() {
        var terms = search.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
        var visible = 0;
        cards.forEach(function (c) {
            var okClan = clan === 'all' || c.getAttribute('data-clan') === clan;
            var name   = c.getAttribute('data-name');
            var okName = terms.every(function (t) { return name.indexOf(t) !== -1; });
            var show   = okClan && okName;
            c.classList.toggle('hidden', !show);
            if (show) { visible++; }
        });
        count.textContent = visible + (visible === 1 ? ' person' : ' people');
        if (none) { none.classList.toggle('hidden', visible !== 0); }
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            clan = tab.getAttribute('data-clan');
            tabs.forEach(function (t) {
                var active = t === tab;
                ON.forEach(function (c)  { t.classList.toggle(c, active); });
                OFF.forEach(function (c) { t.classList.toggle(c, !active); });
            });
            apply();
        });
    });

    if (search) { search.addEventListener('input', apply); }
})();
</script>

<?php $this->load->view('layout/footer'); ?>
