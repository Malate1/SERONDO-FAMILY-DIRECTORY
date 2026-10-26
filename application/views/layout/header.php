<?php defined('BASEPATH') OR exit('No direct script access allowed');
$rep = current_rep();
$asset_version = max(filemtime(FCPATH . 'assets/css/app.css'), filemtime(FCPATH . 'assets/js/navigation.js'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
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
    <link href="<?= base_url('assets/css/app.css') ?>?v=<?= $asset_version ?>" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">

<header class="site-header sticky top-0 z-30 border-b border-slate-200/70 bg-white/85 backdrop-blur">
    <div class="page-gutter mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
        <a href="<?= site_url('clan_members') ?>" class="flex min-w-0 items-center gap-2.5">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 via-emerald-500 to-rose-500 text-white shadow-md">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v6m0 0a3 3 0 100 6 3 3 0 000-6zm-6 12a3 3 0 100-6 3 3 0 000 6zm12 0a3 3 0 100-6 3 3 0 000 6zM6 15V12h12v3"/></svg>
            </span>
            <span class="text-base font-bold leading-tight tracking-tight sm:text-lg">Clan Reunion <span class="block text-slate-400 sm:inline">Directory</span></span>
        </a>

        <details id="mobile-menu" class="mobile-menu shrink-0 lg:hidden">
            <summary id="menu-toggle" aria-controls="primary-nav" class="menu-toggle inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl px-3 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100">
                <svg class="menu-icon-open h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg class="menu-icon-close hidden h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                Menu
            </summary>
            <?php $this->load->view('layout/navigation', ['navigation_id' => 'primary-nav', 'mobile_navigation' => TRUE]); ?>
        </details>
        <?php $this->load->view('layout/navigation', ['navigation_id' => 'desktop-nav', 'mobile_navigation' => FALSE]); ?>
    </div>
</header>

<script src="<?= base_url('assets/js/navigation.js') ?>?v=<?= $asset_version ?>" defer></script>

<main id="main-content" class="app-main page-gutter mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">

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
