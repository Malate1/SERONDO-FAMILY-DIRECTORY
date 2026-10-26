<?php defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('layout/header', ['title' => $title]);
$theme = clan_theme($clan->id);
$input = 'block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15';
$label = 'mb-1.5 block text-sm font-semibold text-slate-700';
$error_open = '<p class="mt-1.5 text-xs font-medium text-rose-600">';
?>

<nav class="mb-6 text-sm text-slate-500" aria-label="Breadcrumb">
    <a href="<?= site_url('clan_members') ?>" class="font-medium transition hover:text-indigo-600">Directory</a>
    <span class="mx-2">/</span>
    <a href="<?= site_url('clan_members/clan/' . $clan->id) ?>" class="font-medium transition hover:text-indigo-600"><?= html_escape($clan->clan_name) ?></a>
    <span class="mx-2">/</span>
    <span class="font-semibold text-slate-800"><?= html_escape($title) ?></span>
</nav>

<div class="mx-auto max-w-3xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
    <div class="bg-gradient-to-br px-6 py-6 sm:px-8 <?= $theme['gradient'] ?>">
        <h1 class="text-2xl font-bold tracking-tight text-white"><?= html_escape($title) ?></h1>
        <p class="mt-2 text-sm text-white/85">Update the name and description shown on your clan page.</p>
    </div>
    <form method="post" action="<?= site_url('clan_members/edit_clan') ?>" class="space-y-5 p-5 sm:p-8">
        <?= csrf_field() ?>
        <?php if (validation_errors()): ?>
            <div data-notification="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" role="alert">Please correct the highlighted fields and try again.</div>
        <?php endif; ?>

        <div>
            <label for="clan-name" class="<?= $label ?>">Clan name</label>
            <input id="clan-name" name="clan_name" type="text" maxlength="100" required value="<?= set_value('clan_name', $clan->clan_name) ?>" class="<?= $input ?>">
            <?= form_error('clan_name', $error_open, '</p>') ?>
        </div>

        <div>
            <label for="clan-description" class="<?= $label ?>">Description <span class="font-normal text-slate-400">(optional)</span></label>
            <textarea id="clan-description" name="description" rows="5" maxlength="2000" class="<?= $input ?>"><?= set_value('description', $clan->description) ?></textarea>
            <?= form_error('description', $error_open, '</p>') ?>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
            <a href="<?= site_url('clan_members/clan/' . $clan->id) ?>" class="touch-target rounded-xl px-5 py-2.5 text-center text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-xl px-6 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 <?= $theme['button'] ?>">Save details</button>
        </div>
    </form>
</div>

<?php $this->load->view('layout/footer'); ?>
