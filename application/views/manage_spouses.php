<?php defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('layout/header', ['title' => isset($title) ? $title : 'Manage spouses']);
$t = clan_theme($clan->id);
$input = 'block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 transition focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15';
$label = 'mb-1.5 block text-sm font-semibold text-slate-700';
$error_open = '<p class="mt-1.5 text-xs font-medium text-rose-600">';
?>

<nav class="mb-6 text-sm text-slate-500" aria-label="Breadcrumb">
    <a href="<?= site_url('clan_members') ?>" class="font-medium transition hover:text-indigo-600">Directory</a>
    <span class="mx-2">/</span>
    <a href="<?= site_url('clan_members/clan/' . $clan->id) ?>" class="font-medium transition hover:text-indigo-600"><?= html_escape($clan->clan_name) ?></a>
    <span class="mx-2">/</span>
    <span class="font-semibold text-slate-800">Spouses</span>
</nav>

<div class="mx-auto max-w-2xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
    <div class="bg-gradient-to-br px-6 py-6 sm:px-8 <?= $t['gradient'] ?>">
        <p class="text-sm font-semibold uppercase tracking-widest text-white/70">Family connections</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-white">Spouses of <?= html_escape(member_full_name($member)) ?></h1>
        <p class="mt-2 text-sm text-white/85">Spouse names are shown on this member’s profile and do not add another generation to the lineage.</p>
    </div>

    <div class="space-y-7 p-5 sm:p-8">
        <section aria-label="Current spouses">
            <h2 class="mb-3 text-sm font-bold text-slate-900">Current spouses</h2>
            <?php if ($spouses): ?>
                <ul class="space-y-3">
                    <?php foreach ($spouses as $spouse): ?>
                        <li class="flex flex-col items-start justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 sm:flex-row sm:items-center">
                            <span class="min-w-0 font-semibold text-slate-800"><?= html_escape(member_full_name($spouse)) ?></span>
                            <form method="post" action="<?= site_url('clan_members/spouses/' . $member->id) ?>" class="shrink-0" onsubmit="return confirm('Remove this spouse name?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="spouse_action" value="delete">
                                <input type="hidden" name="spouse_id" value="<?= (int) $spouse->id ?>">
                                <button type="submit" class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">Remove</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">No spouse names have been added yet.</p>
            <?php endif; ?>
        </section>

        <section class="border-t border-slate-100 pt-6" aria-label="Add spouse">
            <h2 class="mb-3 text-sm font-bold text-slate-900">Add spouse</h2>
            <?php if (validation_errors()): ?>
                <div data-notification="error" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" role="alert">Please correct the highlighted fields.</div>
            <?php endif; ?>
            <form method="post" action="<?= site_url('clan_members/spouses/' . $member->id) ?>" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="spouse_action" value="add">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="spouse-first-name" class="<?= $label ?>">First name</label>
                        <input id="spouse-first-name" name="first_name" type="text" maxlength="100" required value="<?= set_value('first_name') ?>" class="<?= $input ?>">
                        <?= form_error('first_name', $error_open, '</p>') ?>
                    </div>
                    <div>
                        <label for="spouse-last-name" class="<?= $label ?>">Last name</label>
                        <input id="spouse-last-name" name="last_name" type="text" maxlength="100" required value="<?= set_value('last_name') ?>" class="<?= $input ?>">
                        <?= form_error('last_name', $error_open, '</p>') ?>
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="<?= site_url('clan_members/clan/' . $clan->id) ?>#m-<?= (int) $member->id ?>" class="touch-target rounded-xl px-5 py-2.5 text-center text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">Back to clan</a>
                    <button type="submit" class="rounded-xl px-6 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 <?= $t['button'] ?>">Add spouse</button>
                </div>
            </form>
        </section>
    </div>
</div>

<?php $this->load->view('layout/footer'); ?>
