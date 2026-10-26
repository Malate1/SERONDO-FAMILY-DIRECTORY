<?php defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('layout/header', ['title' => $title]);
$theme = clan_theme($clan->id);
$input = 'block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15';
$label = 'mb-1.5 block text-sm font-semibold text-slate-700';
$error_open = '<p class="mt-1.5 text-xs font-medium text-rose-600">';
$event_value = function ($field, $format = NULL) use ($event) {
    if ($event && isset($event->$field) && $event->$field)
    {
        $date = new DateTime($event->$field);
        return $format ? $date->format($format) : $event->$field;
    }
    return '';
};
?>

<nav class="mb-6 text-sm text-slate-500" aria-label="Breadcrumb">
    <a href="<?= site_url('clan_members') ?>" class="font-medium transition hover:text-indigo-600">Directory</a>
    <span class="mx-2">/</span>
    <a href="<?= site_url('events') ?>" class="font-medium transition hover:text-indigo-600">Clan Calendar</a>
    <span class="mx-2">/</span>
    <span class="font-semibold text-slate-800"><?= html_escape($title) ?></span>
</nav>

<div class="mx-auto max-w-3xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
    <div class="bg-gradient-to-br px-6 py-6 sm:px-8 <?= $theme['gradient'] ?>">
        <h1 class="text-2xl font-bold tracking-tight text-white"><?= html_escape($title) ?></h1>
        <p class="mt-2 text-sm text-white/85">Share a clan event or add it to the calendar for all clans.</p>
    </div>
    <form method="post" action="<?= $event ? site_url('events/edit/' . $event->id) : site_url('events/create') ?>" class="space-y-5 p-5 sm:p-8">
        <?= csrf_field() ?>
        <?php if (validation_errors()): ?>
            <div data-notification="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" role="alert">Please correct the highlighted fields and try again.</div>
        <?php endif; ?>

        <div>
            <label for="event-title" class="<?= $label ?>">Event name</label>
            <input id="event-title" name="title" type="text" maxlength="160" required value="<?= set_value('title', $event ? $event->title : '') ?>" class="<?= $input ?>">
            <?= form_error('title', $error_open, '</p>') ?>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="event-category" class="<?= $label ?>">Category</label>
                <select id="event-category" name="category" required class="<?= $input ?>">
                    <?php foreach (['reunion' => 'Reunion / gathering', 'celebration' => 'Birthday / celebration', 'memorial' => 'Necrological / interment'] as $key => $name): ?>
                        <option value="<?= $key ?>" <?= set_select('category', $key, $event && $event->category === $key) ?>><?= html_escape($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= form_error('category', $error_open, '</p>') ?>
            </div>
            <div>
                <label for="event-scope" class="<?= $label ?>">Who is this event for?</label>
                <select id="event-scope" name="scope" required class="<?= $input ?>">
                    <option value="all" <?= $scope === 'all' ? 'selected' : '' ?>>All clans</option>
                    <option value="own" <?= $scope === 'own' ? 'selected' : '' ?>><?= html_escape($clan->clan_name) ?> only</option>
                </select>
                <?= form_error('scope', $error_open, '</p>') ?>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="event-start" class="<?= $label ?>">Starts</label>
                <input id="event-start" name="starts_at" type="datetime-local" required value="<?= set_value('starts_at', $event_value('starts_at', 'Y-m-d\TH:i')) ?>" class="js-datetime-picker <?= $input ?>">
                <?= form_error('starts_at', $error_open, '</p>') ?>
            </div>
            <div>
                <label for="event-end" class="<?= $label ?>">Ends <span class="font-normal text-slate-400">(optional)</span></label>
                <input id="event-end" name="ends_at" type="datetime-local" value="<?= set_value('ends_at', $event_value('ends_at', 'Y-m-d\TH:i')) ?>" class="js-datetime-picker <?= $input ?>">
                <?= form_error('ends_at', $error_open, '</p>') ?>
            </div>
        </div>

        <div>
            <label for="event-location" class="<?= $label ?>">Location <span class="font-normal text-slate-400">(optional)</span></label>
            <input id="event-location" name="location" type="text" maxlength="255" value="<?= set_value('location', $event ? $event->location : '') ?>" class="<?= $input ?>">
            <?= form_error('location', $error_open, '</p>') ?>
        </div>

        <div>
            <label for="event-description" class="<?= $label ?>">Details <span class="font-normal text-slate-400">(optional)</span></label>
            <textarea id="event-description" name="description" rows="4" maxlength="3000" class="<?= $input ?>"><?= set_value('description', $event ? $event->description : '') ?></textarea>
            <?= form_error('description', $error_open, '</p>') ?>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
            <a href="<?= site_url('events') ?>" class="touch-target rounded-xl px-5 py-2.5 text-center text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-xl px-6 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 <?= $theme['button'] ?>"><?= $event ? 'Save event' : 'Create event' ?></button>
        </div>
    </form>
</div>

<?php $this->load->view('layout/footer'); ?>
