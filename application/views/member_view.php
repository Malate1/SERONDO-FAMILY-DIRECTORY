<?php defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('layout/header', ['title' => $title]);
$t = clan_theme($clan->id);
$name = member_full_name($member);
$deceased = ! empty($member->is_deceased);
$age = $deceased ? NULL : member_age($member->birth_date);
$can_add_children = $can_manage && (int) $member->generation < (int) $max_generation;
?>

<nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
    <a href="<?= site_url('clan_members') ?>" class="font-medium hover:text-indigo-600">Directory</a>
    <span aria-hidden="true">/</span>
    <a href="<?= site_url('clan_members/clan/' . $clan->id) ?>" class="font-medium hover:text-indigo-600"><?= html_escape($clan->clan_name) ?></a>
    <span aria-hidden="true">/</span>
    <span aria-current="page" class="font-semibold text-slate-800"><?= html_escape($name) ?></span>
</nav>

<section class="mb-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" aria-labelledby="member-title">
    <div class="h-2 <?= $t['solid'] ?>"></div>
    <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:p-8">
        <?= member_avatar($member, 'h-20 w-20', 'text-2xl') ?>
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Family branch &middot; <?= html_escape(generation_label($member->generation)) ?></p>
            <h1 id="member-title" class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl"><?= html_escape($name) ?></h1>
            <p class="mt-2 text-sm text-slate-500"><?= html_escape(member_birth_label($member->birth_date)) ?><?= $age !== NULL ? ' &middot; ' . $age . ' yrs' : '' ?></p>
            <?php if ($deceased): ?>
                <a href="<?= site_url('memorials') ?>#memorial-<?= (int) $member->id ?>" class="mt-2 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">In memoriam<?= ! empty($member->death_date) ? ' &middot; ' . html_escape(date('M j, Y', strtotime($member->death_date))) : '' ?></a>
            <?php endif; ?>
        </div>
        <?php if ($can_manage): ?>
            <div class="flex flex-wrap gap-2">
                <a href="<?= site_url('clan_members/edit/' . $member->id) ?>" class="touch-target rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">Edit details</a>
                <?php if ($can_add_children): ?>
                    <a href="<?= site_url('clan_members/children/' . $member->id) ?>" class="touch-target rounded-xl px-4 py-2.5 text-sm font-semibold text-white <?= $t['button'] ?>">+ Add children</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
    <aside class="space-y-5" aria-label="Family connections">
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-bold text-slate-900">Parent</h2>
            <?php if ($member->parent_id !== NULL): ?>
                <a href="<?= site_url('clan_members/member/' . $member->parent_id) ?>" class="touch-target mt-2 text-sm font-semibold text-indigo-700 hover:underline"><?= html_escape($member->parent_name) ?> &rarr;</a>
            <?php else: ?>
                <p class="mt-2 text-sm text-slate-500">Clan ancestor</p>
            <?php endif; ?>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-bold text-slate-900">Spouses</h2>
                <?php if ($can_manage): ?>
                    <a href="<?= site_url('clan_members/spouses/' . $member->id) ?>" class="touch-target text-xs font-semibold text-indigo-700 hover:underline">Manage spouses</a>
                <?php endif; ?>
            </div>
            <?php if ($spouses): ?>
                <ul class="mt-2 space-y-2 text-sm text-slate-600">
                    <?php foreach ($spouses as $spouse): ?>
                        <li><?= html_escape(member_full_name($spouse)) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="mt-2 text-sm text-slate-500">No spouses recorded.</p>
            <?php endif; ?>
        </section>
        <a href="<?= site_url('clan_members/clan/' . $clan->id) ?>" class="touch-target text-sm font-semibold text-slate-600 hover:text-indigo-700">View the whole clan &rarr;</a>
        <?php if ($can_manage): ?>
            <details class="rounded-2xl border border-slate-200 bg-white p-5">
                <summary class="cursor-pointer text-sm font-semibold text-slate-500">Member actions</summary>
                <?php if ($children): ?>
                    <p class="mt-3 text-xs text-slate-500">Remove or move this member's children before deleting this member.</p>
                <?php else: ?>
                    <form method="post" action="<?= site_url('clan_members/delete/' . $member->id) ?>" data-confirm-title="Remove family member?" data-confirm-text="<?= html_escape($name . ' and their photo will be permanently removed from the tree.') ?>" data-confirm-danger="true" data-confirm-button="Remove member" data-submit-label="Removing..." class="mt-3">
                        <?= csrf_field() ?>
                        <button type="submit" class="touch-target rounded-xl bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">Delete member</button>
                    </form>
                <?php endif; ?>
            </details>
        <?php endif; ?>
    </aside>

    <section id="children" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="children-title">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="children-title" class="text-lg font-bold text-slate-900">Children <span class="ml-1 rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-600"><?= count($children) ?></span></h2>
                <p class="mt-1 text-sm text-slate-500">Children of <?= html_escape($name) ?>. Open a child to explore their family.</p>
            </div>
        </div>
        <?php if ($children): ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($children as $child): ?>
                    <li>
                        <a href="<?= site_url('clan_members/member/' . $child->id) ?>" class="flex items-center gap-3 rounded-xl px-2 py-4 transition hover:bg-slate-50">
                            <?= member_avatar($child, 'h-12 w-12', 'text-sm') ?>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-900"><?= html_escape(member_full_name($child)) ?></span>
                                <span class="mt-1 block text-xs text-slate-500"><?= html_escape(generation_label($child->generation)) ?> &middot; <?= html_escape(member_birth_label($child->birth_date)) ?><?= ! empty($child->is_deceased) ? ' &middot; In memoriam' : '' ?></span>
                            </span>
                            <span aria-hidden="true" class="text-slate-400">&rarr;</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-10 text-center">
                <p class="text-sm font-semibold text-slate-700">No children recorded yet</p>
                <p class="mt-2 text-sm text-slate-500"><?= $can_add_children ? 'Add children to start building this family branch.' : 'Recorded children will appear here.' ?></p>
                <?php if ($can_add_children): ?>
                    <a href="<?= site_url('clan_members/children/' . $member->id) ?>" class="touch-target mt-4 rounded-xl px-4 py-2 text-sm font-semibold text-white <?= $t['button'] ?>">+ Add children</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($can_manage && ! $can_add_children): ?>
            <p class="mt-4 text-xs text-slate-500">This member is in the deepest allowed generation.</p>
        <?php endif; ?>
    </section>
</div>

<?php $this->load->view('layout/footer'); ?>
