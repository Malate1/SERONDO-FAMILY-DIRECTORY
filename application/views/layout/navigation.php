<?php defined('BASEPATH') OR exit('No direct script access allowed');
$navigation_rep = current_rep();
?>
<nav id="<?= html_escape($navigation_id) ?>" aria-label="Main navigation" class="text-sm font-medium <?= $mobile_navigation ? 'site-nav flex flex-col items-stretch gap-2 px-4 py-3 sm:px-6' : 'desktop-nav hidden items-center gap-2 lg:flex' ?>">
    <a href="<?= site_url('events') ?>" class="touch-target rounded-lg px-3 py-2.5 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Clan Calendar</a>
    <a href="<?= site_url('memorials') ?>" class="touch-target rounded-lg px-3 py-2.5 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">In Memoriam</a>
    <?php if ($navigation_rep): $navigation_theme = clan_theme($navigation_rep->clan_id); ?>
        <a href="<?= site_url('clan_members/clan/' . $navigation_rep->clan_id) ?>" class="touch-target flex min-w-0 items-center gap-2 rounded-xl px-3 py-2.5 ring-1 lg:max-w-56 lg:rounded-full <?= $navigation_theme['soft'] ?>">
            <span class="h-2 w-2 shrink-0 rounded-full <?= $navigation_theme['solid'] ?>"></span>
            <span class="truncate"><?= html_escape($navigation_rep->full_name) ?></span>
        </a>
        <form data-confirm-title="Sign out?" data-confirm-text="You will need to sign in again to manage your clan." data-confirm-button="Sign out" data-submit-label="Signing out…" method="post" action="<?= site_url('logout') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="touch-target w-full rounded-lg px-3 py-2.5 text-slate-600 transition hover:bg-slate-100">Sign out</button>
        </form>
    <?php else: ?>
        <a href="<?= site_url('login') ?>" class="touch-target rounded-lg bg-slate-900 px-4 py-2.5 text-white transition hover:bg-slate-700">Representative login</a>
    <?php endif; ?>
</nav>
