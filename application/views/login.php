<?php defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('layout/header', ['title' => isset($title) ? $title : 'Representative Login']);
$input = 'block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm shadow-sm placeholder:text-slate-400 transition focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15';
?>

<div class="mx-auto mt-6 max-w-md sm:mt-12">
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
        <div class="bg-gradient-to-br from-indigo-600 via-emerald-600 to-rose-600 px-5 py-6 text-center sm:px-8 sm:py-8">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 text-white ring-1 ring-white/40">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 10-8 0v4m-2 0h12a1 1 0 011 1v7a1 1 0 01-1 1H6a1 1 0 01-1-1v-7a1 1 0 011-1z"/></svg>
            </span>
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-white">Clan Representative</h1>
            <p class="mt-1 text-sm text-white/80">Sign in to manage your clan's branch of the tree.</p>
        </div>

        <form data-confirm-title="Sign in?" data-confirm-text="Continue to your representative account?" data-confirm-fields="username" data-confirm-button="Sign in" data-submit-label="Signing in…" method="post" action="<?= site_url('login') ?>" class="space-y-5 p-5 sm:p-8" autocomplete="on">
            <?= csrf_field() ?>

            <?php if (validation_errors()): ?>
                <div data-notification="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" role="alert">Please correct the highlighted sign-in fields.</div>
            <?php endif; ?>

            <?php if ( ! empty($error)): ?>
                <div data-notification="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" role="alert"><?= html_escape($error) ?></div>
            <?php endif; ?>

            <div>
                <label for="username" class="mb-1.5 block text-sm font-semibold text-slate-700">Username</label>
                <input type="text" id="username" name="username" required maxlength="50" autocomplete="username" autocapitalize="none" spellcheck="false"
                       value="<?= set_value('username') ?>" placeholder="e.g. rep_alpha" class="<?= $input ?>">
                <?= form_error('username', '<p class="mt-1.5 text-xs font-medium text-rose-600">', '</p>') ?>
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" required maxlength="200" autocomplete="current-password" class="<?= $input ?> pr-16">
                    <button type="button" id="toggle-pw" class="absolute inset-y-0 right-0 px-4 text-xs font-semibold text-slate-500 hover:text-slate-800">Show</button>
                </div>
                <?= form_error('password', '<p class="mt-1.5 text-xs font-medium text-rose-600">', '</p>') ?>
            </div>

            <button type="submit" class="w-full rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-slate-700 active:translate-y-0">
                Sign in
            </button>

            <p class="text-center text-xs text-slate-400">Only designated representatives can sign in. Everyone else can browse the directory freely.</p>
        </form>
    </div>

    <p class="mt-6 text-center text-sm"><a href="<?= site_url('clan_members') ?>" class="font-medium text-slate-500 transition hover:text-indigo-600">&larr; Back to the directory</a></p>
</div>

<script>
document.getElementById('toggle-pw').addEventListener('click', function () {
    var f = document.getElementById('password');
    var show = f.type === 'password';
    f.type = show ? 'text' : 'password';
    this.textContent = show ? 'Hide' : 'Show';
});
</script>

<?php $this->load->view('layout/footer'); ?>
