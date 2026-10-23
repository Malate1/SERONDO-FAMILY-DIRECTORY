<?php defined('BASEPATH') OR exit('No direct script access allowed');
$rep = current_rep();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape(isset($title) ? $title : 'Reunion Directory') ?> · Clan Reunion Directory</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['"Google Sans"', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] } } }
        };
    </script>
    <style>
        details > summary { list-style: none; }
        details > summary::-webkit-details-marker { display: none; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">

<header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/85 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <a href="<?= site_url('clan_members') ?>" class="flex items-center gap-2.5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 via-emerald-500 to-rose-500 text-white shadow-md">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v6m0 0a3 3 0 100 6 3 3 0 000-6zm-6 12a3 3 0 100-6 3 3 0 000 6zm12 0a3 3 0 100-6 3 3 0 000 6zM6 15V12h12v3"/></svg>
            </span>
            <span class="text-lg font-bold tracking-tight">Clan Reunion <span class="text-slate-400">Directory</span></span>
        </a>

        <nav class="flex items-center gap-2 text-sm font-medium">
            <a href="<?= site_url('events') ?>" class="rounded-lg px-3 py-1.5 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Clan Calendar</a>
            <a href="<?= site_url('memorials') ?>" class="rounded-lg px-3 py-1.5 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">In Memoriam</a>
            <?php if ($rep): $t = clan_theme($rep->clan_id); ?>
                <a href="<?= site_url('clan_members/clan/' . $rep->clan_id) ?>" class="hidden items-center gap-2 rounded-full px-3 py-1.5 ring-1 sm:inline-flex <?= $t['soft'] ?>">
                    <span class="h-2 w-2 rounded-full <?= $t['solid'] ?>"></span>
                    <?= html_escape($rep->full_name) ?>
                </a>
                <form method="post" action="<?= site_url('logout') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="rounded-lg px-3 py-1.5 text-slate-600 transition hover:bg-slate-100">Sign out</button>
                </form>
            <?php else: ?>
                <a href="<?= site_url('login') ?>" class="rounded-lg bg-slate-900 px-4 py-2 text-white transition hover:bg-slate-700">Representative login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

<?php if ($this->session->flashdata('success')): ?>
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-sm" role="status">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>
        <?= html_escape($this->session->flashdata('success')) ?>
    </div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 shadow-sm" role="alert">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 6a1 1 0 112 0v4a1 1 0 11-2 0V6zm1 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
        <?= html_escape($this->session->flashdata('error')) ?>
    </div>
<?php endif; ?>
