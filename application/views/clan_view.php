<?php defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('layout/header', ['title' => isset($title) ? $title : $clan->clan_name]);

$t     = clan_theme($clan->id);
$total = count($members);

// How many children each member has (used to disable "delete" for parents).
$child_count = [];
foreach ($members as $m)
{
    if ($m->parent_id !== NULL)
    {
        $pid = (int) $m->parent_id;
        $child_count[$pid] = isset($child_count[$pid]) ? $child_count[$pid] + 1 : 1;
    }
}

$btn_edit = 'touch-target rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-200';

/** One lineage-tree node card. */
$node_card = function ($n) use ($can_manage, $t, $child_count, $spouses) {
    $kids = isset($child_count[(int) $n->id]) ? $child_count[(int) $n->id] : 0;
    $partners = isset($spouses[(int) $n->id]) ? $spouses[(int) $n->id] : [];
    $deceased = isset($n->is_deceased) && (int) $n->is_deceased === 1;
    $age  = $deceased ? NULL : member_age($n->birth_date);
    $html  = '<div id="t-' . (int) $n->id . '" class="person lineage-person flex items-center gap-3 rounded-2xl border bg-white p-3 shadow-sm transition ' . $t['border'] . '"'
           . ' data-view="tree" data-gen="' . (int) $n->generation . '" data-name="' . html_escape(mb_strtolower(member_full_name($n))) . '">';
    if ($kids)
    {
        $html .= '<svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>';
    }
    else
    {
        $html .= '<span class="h-4 w-4 shrink-0"></span>';
    }
    $html .= member_avatar($n, 'h-11 w-11', 'text-sm')
           . '<div class="min-w-0 flex-1"><p class="lineage-name truncate text-sm font-bold text-slate-900">' . html_escape(member_full_name($n)) . '</p>'
           . '<p class="truncate text-xs text-slate-500">' . html_escape(generation_label($n->generation))
           . ($n->birth_date ? ' · b. ' . html_escape(date('Y', strtotime($n->birth_date))) : '')
           . ($deceased && $n->death_date ? ' · d. ' . html_escape(date('Y', strtotime($n->death_date))) : '')
           . ($age !== NULL ? ' (' . $age . ')' : '') . '</p></div>';
    if ($deceased)
    {
        $html .= '<span class="lineage-status rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">In memoriam</span>';
    }
    if ($partners)
    {
        $partner_names = [];
        foreach ($partners as $partner)
        {
            $partner_names[] = member_full_name($partner);
        }
        $html .= '<span class="hidden max-w-40 truncate text-xs text-indigo-600 lg:inline">Spouse: ' . html_escape(implode(', ', $partner_names)) . '</span>';
    }
    if ($kids)
    {
        $html .= '<span class="hidden rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 sm:inline">' . $kids . ($kids === 1 ? ' child' : ' children') . '</span>';
    }
    if ($can_manage)
    {
        $html .= '<div class="lineage-actions flex shrink-0 gap-2">';
        $html .= '<a href="' . site_url('clan_members/spouses/' . $n->id) . '" class="touch-target rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Spouses</a>';
        $html .= '<a href="' . site_url('clan_members/edit/' . $n->id) . '" class="touch-target rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">Edit</a></div>';
    }
    return $html . '</div>';
};

/** Recursive renderer (nested <details> so branches can be collapsed). */
$render_tree = function ($nodes, $depth) use (&$render_tree, $node_card, $t) {
    echo '<ul class="space-y-3 ' . ($depth ? 'lineage-branch ml-6 border-l-2 pl-5 ' . $t['line'] : '') . '">';
    foreach ($nodes as $n)
    {
        echo '<li>';
        if ( ! empty($n->children))
        {
            echo '<details open class="group"><summary class="cursor-pointer select-none">' . $node_card($n) . '</summary>';
            $render_tree($n->children, $depth + 1);
            echo '</details>';
        }
        else
        {
            echo $node_card($n);
        }
        echo '</li>';
    }
    echo '</ul>';
};
?>

<nav class="mb-6 text-sm text-slate-500" aria-label="Breadcrumb">
    <a href="<?= site_url('clan_members') ?>" class="font-medium transition hover:text-indigo-600">Directory</a>
    <span class="mx-2">/</span>
    <span class="font-semibold text-slate-800"><?= html_escape($clan->clan_name) ?></span>
</nav>

<!-- Clan banner -->
<section class="mb-8 overflow-hidden rounded-3xl bg-gradient-to-br p-6 shadow-xl sm:p-10 <?= $t['gradient'] ?>">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="text-white">
            <p class="text-sm font-semibold uppercase tracking-widest text-white/70">Clan branch</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight sm:text-4xl"><?= html_escape($clan->clan_name) ?></h1>
            <?php if ( ! empty($clan->description)): ?>
                <p class="mt-2 max-w-2xl text-white/85"><?= html_escape($clan->description) ?></p>
            <?php endif; ?>
            <div class="mt-5 flex flex-wrap gap-2">
                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold ring-1 ring-white/30"><?= (int) $stats->member_count ?> members</span>
                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold ring-1 ring-white/30"><?= (int) $stats->max_generation ?> generations</span>
            </div>
        </div>
        <?php if ($can_manage): ?>
            <div class="flex flex-wrap gap-3">
                <a href="<?= site_url('clan_members/edit_clan') ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/15 px-5 py-3 text-sm font-semibold text-white ring-1 ring-white/30 transition hover:bg-white/25">
                    Edit clan details
                </a>
                <a href="<?= site_url('clan_members/add') ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-lg transition hover:-translate-y-0.5 hover:bg-slate-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    Add member
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($total === 0): ?>
    <div class="rounded-3xl border-2 border-dashed border-slate-300 bg-white px-6 py-16 text-center">
        <p class="text-lg font-bold text-slate-800">This branch is empty</p>
        <p class="mt-1 text-sm text-slate-500">
            <?= $can_manage ? 'Start with the clan ancestor (Generation 1).' : 'The clan representative has not added anyone yet.' ?>
        </p>
        <?php if ($can_manage): ?>
            <a href="<?= site_url('clan_members/add') ?>" class="mt-5 inline-block rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-md <?= $t['button'] ?>">Add the clan ancestor</a>
        <?php endif; ?>
    </div>
<?php else: ?>

    <!-- Controls -->
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="inline-flex rounded-xl bg-white p-1 shadow-sm ring-1 ring-slate-200" role="group" aria-label="View">
            <button type="button" data-view-btn="grid" class="view-btn rounded-lg px-4 py-2 text-sm font-semibold text-white <?= $t['solid'] ?>">Grid</button>
            <button type="button" data-view-btn="tree" class="view-btn rounded-lg px-4 py-2 text-sm font-semibold text-slate-600">Lineage tree</button>
        </div>
        <div class="relative w-full lg:w-96">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/></svg>
            <label for="search" class="sr-only">Search this family branch</label>
            <input id="search" type="search" autocomplete="off" placeholder="Search this branch…"
                   class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-4 text-sm shadow-sm placeholder:text-slate-400 transition focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15">
        </div>
    </div>

    <!-- Generation filter -->
    <div id="gen-filter" class="mb-6 flex flex-wrap items-center gap-2">
        <span class="mr-1 text-xs font-semibold uppercase tracking-wider text-slate-400">Generation</span>
        <button type="button" data-gen="all" class="gen-chip rounded-full bg-slate-900 px-3.5 py-1.5 text-xs font-semibold text-white">All</button>
        <?php foreach ($stats->per_generation as $g => $n): ?>
            <button type="button" data-gen="<?= (int) $g ?>" class="gen-chip rounded-full bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                Gen <?= (int) $g ?> <span class="text-slate-400">· <?= (int) $n ?></span>
            </button>
        <?php endforeach; ?>
        <span id="count" class="ml-auto text-xs font-semibold text-slate-500"><?= $total ?> shown</span>
    </div>

    <!-- GRID VIEW -->
    <div id="grid-view">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <?php foreach ($members as $m):
                $deceased = isset($m->is_deceased) && (int) $m->is_deceased === 1;
                $age  = $deceased ? NULL : member_age($m->birth_date);
                $kids = isset($child_count[(int) $m->id]) ? $child_count[(int) $m->id] : 0; ?>
                <article id="m-<?= (int) $m->id ?>"
                         class="person flex scroll-mt-24 flex-col items-center rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl <?= $t['hover'] ?>"
                         data-view="grid" data-gen="<?= (int) $m->generation ?>"
                         data-name="<?= html_escape(mb_strtolower(member_full_name($m))) ?>">
                    <?= member_avatar($m, 'h-24 w-24', 'text-3xl') ?>
                    <h3 class="mt-4 text-base font-bold leading-tight text-slate-900"><?= html_escape(member_full_name($m)) ?></h3>
                    <?php if ($deceased): ?>
                        <p class="mt-1 text-sm text-slate-500">
                            <?= $m->birth_date ? html_escape(date('M j, Y', strtotime($m->birth_date))) : 'Birth date not set' ?>
                            <?= $m->death_date ? ' — ' . html_escape(date('M j, Y', strtotime($m->death_date))) : '' ?>
                        </p>
                        <a href="<?= site_url('memorials') ?>#memorial-<?= (int) $m->id ?>" class="mt-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-200">In memoriam</a>
                    <?php else: ?>
                        <p class="mt-1 text-sm text-slate-500"><?= html_escape(member_birth_label($m->birth_date)) ?><?= $age !== NULL ? ' · ' . $age . ' yrs' : '' ?></p>
                    <?php endif; ?>
                    <div class="mt-3 flex flex-wrap justify-center gap-1.5">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 <?= $t['soft'] ?>"><?= html_escape(generation_label($m->generation)) ?></span>
                    </div>
                    <p class="mobile-wrap mt-2 max-w-full truncate text-xs text-slate-500"><?= $m->parent_name ? 'Child of ' . html_escape($m->parent_name) : 'Clan ancestor' ?></p>
                    <?php if (isset($spouses[(int) $m->id]) && $spouses[(int) $m->id]): ?>
                        <p class="mobile-wrap mt-1 max-w-full truncate text-xs font-medium text-indigo-600">
                            Spouse<?= count($spouses[(int) $m->id]) > 1 ? 's' : '' ?>:
                            <?php
                            $spouse_names = [];
                            foreach ($spouses[(int) $m->id] as $spouse)
                            {
                                $spouse_names[] = member_full_name($spouse);
                            }
                            echo html_escape(implode(', ', $spouse_names));
                            ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($can_manage): ?>
                        <div class="mt-5 flex w-full items-center gap-2 border-t border-slate-100 pt-4">
                            <a href="<?= site_url('clan_members/spouses/' . $m->id) ?>" class="touch-target flex-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-center text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100">Spouses</a>
                            <a href="<?= site_url('clan_members/edit/' . $m->id) ?>" class="flex-1 <?= $btn_edit ?>">Edit</a>
                            <?php if ($kids): ?>
                                <span title="Remove or move this member's children first" class="cursor-not-allowed rounded-lg bg-slate-50 p-2 text-slate-300">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 12a1 1 0 001 1h6a1 1 0 001-1l1-12"/></svg>
                                </span>
                            <?php else: ?>
                                <form data-confirm-title="Remove family member?" data-confirm-text="<?= html_escape(member_full_name($m) . ' and their photo will be permanently removed from the tree.') ?>" data-confirm-danger="true" data-confirm-button="Remove member" data-submit-label="Removing…" method="post" action="<?= site_url('clan_members/delete/' . $m->id) ?>" class="shrink-0">
                                    <?= csrf_field() ?>
                                    <button type="submit" aria-label="Delete <?= html_escape(member_full_name($m)) ?>" class="rounded-lg bg-rose-50 p-2 text-rose-600 transition hover:bg-rose-100">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 12a1 1 0 001 1h6a1 1 0 001-1l1-12"/></svg>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TREE VIEW -->
    <div id="tree-view" class="hidden overflow-x-auto rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="mb-4 flex flex-wrap gap-2">
            <button type="button" id="expand-all" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">Expand all</button>
            <button type="button" id="collapse-all" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">Collapse all</button>
        </div>
        <div class="min-w-0">
            <?php $render_tree($tree, 0); ?>
        </div>
    </div>

    <div id="no-results" class="mt-6 hidden rounded-3xl border border-slate-200 bg-white px-6 py-14 text-center shadow-sm">
        <p class="font-bold text-slate-800">No matching members</p>
        <p class="mt-1 text-sm text-slate-500">Try a different name or generation.</p>
    </div>
<?php endif; ?>

<script>
(function () {
    var THEME_ON = <?= json_encode($t['solid']) ?>;
    var people   = Array.prototype.slice.call(document.querySelectorAll('.person'));
    var search   = document.getElementById('search');
    var count    = document.getElementById('count');
    var none     = document.getElementById('no-results');
    var gridView = document.getElementById('grid-view');
    var treeView = document.getElementById('tree-view');
    var chips    = Array.prototype.slice.call(document.querySelectorAll('.gen-chip'));
    var viewBtns = Array.prototype.slice.call(document.querySelectorAll('.view-btn'));
    var gen = 'all', mode = 'grid';

    function apply() {
        if (!search) { return; }
        var terms = search.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
        var shown = 0, filtering = terms.length > 0 || gen !== 'all';

        people.forEach(function (p) {
            var okGen  = gen === 'all' || p.getAttribute('data-gen') === gen;
            var name   = p.getAttribute('data-name');
            var okName = terms.every(function (t) { return name.indexOf(t) !== -1; });
            var match  = okGen && okName;
            var isGrid = p.getAttribute('data-view') === 'grid';

            if (isGrid) {
                p.classList.toggle('hidden', !match);
                if (match) { shown++; }
            } else {
                // Tree keeps its shape: dim non-matches, outline matches.
                p.classList.toggle('opacity-30', filtering && !match);
                p.classList.toggle('ring-2', filtering && match);
                p.classList.toggle('ring-slate-400', filtering && match);
            }
        });

        if (mode === 'tree') {
            var matches = people.filter(function (p) { return p.getAttribute('data-view') === 'tree' && !p.classList.contains('opacity-30'); });
            shown = matches.length;
            if (filtering) { document.querySelectorAll('#tree-view details').forEach(function (d) { d.open = true; }); }
        }
        count.textContent = shown + ' shown';
        none.classList.toggle('hidden', shown !== 0);
    }

    function setView(next) {
        mode = next;
        gridView.classList.toggle('hidden', next !== 'grid');
        treeView.classList.toggle('hidden', next !== 'tree');
        viewBtns.forEach(function (b) {
            var on = b.getAttribute('data-view-btn') === next;
            b.classList.toggle(THEME_ON, on);
            b.classList.toggle('text-white', on);
            b.classList.toggle('text-slate-600', !on);
        });
        apply();
    }

    viewBtns.forEach(function (b) { b.addEventListener('click', function () { setView(b.getAttribute('data-view-btn')); }); });

    chips.forEach(function (c) {
        c.addEventListener('click', function () {
            gen = c.getAttribute('data-gen');
            chips.forEach(function (x) {
                var on = x === c;
                x.classList.toggle('bg-slate-900', on);   x.classList.toggle('text-white', on);
                x.classList.toggle('bg-white', !on);      x.classList.toggle('text-slate-700', !on);
                x.classList.toggle('ring-1', !on);        x.classList.toggle('ring-slate-200', !on);
            });
            apply();
        });
    });

    if (search) { search.addEventListener('input', apply); }

    var ex = document.getElementById('expand-all'), co = document.getElementById('collapse-all');
    if (ex) { ex.addEventListener('click', function () { document.querySelectorAll('#tree-view details').forEach(function (d) { d.open = true; }); }); }
    if (co) { co.addEventListener('click', function () { document.querySelectorAll('#tree-view details').forEach(function (d) { d.open = false; }); }); }

    // Deep link from the dashboard (#m-ID): scroll to and flash that member.
    if (location.hash.indexOf('#m-') === 0) {
        var el = document.querySelector(location.hash);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.add('ring-4', 'ring-amber-300');
            setTimeout(function () { el.classList.remove('ring-4', 'ring-amber-300'); }, 2500);
        }
    }

})();
</script>

<?php $this->load->view('layout/footer'); ?>
