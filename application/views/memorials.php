<?php defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('layout/header', ['title' => isset($title) ? $title : 'In Memoriam']);
?>

<nav class="mb-6 text-sm text-slate-500" aria-label="Breadcrumb">
    <a href="<?= site_url('clan_members') ?>" class="font-medium transition hover:text-indigo-600">Directory</a>
    <span class="mx-2">/</span>
    <span class="font-semibold text-slate-800">In Memoriam</span>
</nav>

<section class="mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-slate-800 via-slate-700 to-slate-900 p-6 shadow-xl sm:p-10">
    <p class="text-sm font-semibold uppercase tracking-widest text-slate-300">Remembering our family</p>
    <h1 class="mt-1 text-3xl font-bold tracking-tight text-white sm:text-4xl">In Memoriam</h1>
    <p class="mt-3 max-w-2xl text-slate-200">We honor the lives, stories, and enduring legacy of our family members.</p>
</section>

<?php if ($memorials): ?>
    <section aria-label="Family memorials" class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <?php foreach ($memorials as $member): ?>
            <article id="memorial-<?= (int) $member->id ?>" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col items-start gap-4 border-b border-slate-100 p-5 min-[400px]:flex-row min-[400px]:items-center sm:p-6">
                    <?= member_avatar($member, 'h-20 w-20', 'text-2xl') ?>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500"><?= html_escape($member->clan_name) ?></p>
                        <h2 class="mt-1 text-xl font-bold text-slate-900"><?= html_escape(member_full_name($member)) ?></h2>
                        <?php if ($member->birth_date || $member->death_date): ?>
                            <p class="mt-1 text-sm text-slate-500">
                                <?= $member->birth_date ? html_escape(date('M j, Y', strtotime($member->birth_date))) : 'Birth date unknown' ?>
                                <span class="mx-1">—</span>
                                <?= $member->death_date ? html_escape(date('M j, Y', strtotime($member->death_date))) : 'Passing date not recorded' ?>
                            </p>
                        <?php else: ?>
                            <p class="mt-1 text-sm text-slate-500">Dates not recorded</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="p-5 sm:p-6">
                    <?php if ( ! empty($member->tribute)): ?>
                        <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700"><?= html_escape($member->tribute) ?></p>
                    <?php else: ?>
                        <p class="text-sm italic text-slate-500">Remembered with love by the family.</p>
                    <?php endif; ?>
                    <a href="<?= site_url('clan_members/clan/' . $member->clan_id) ?>#m-<?= (int) $member->id ?>" class="touch-target mt-5 inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 transition hover:text-indigo-700">
                        View family branch
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php else: ?>
    <div class="rounded-3xl border-2 border-dashed border-slate-300 bg-white px-6 py-16 text-center">
        <p class="text-lg font-bold text-slate-800">No memorials have been added yet</p>
        <p class="mt-1 text-sm text-slate-500">Clan representatives can add a memorial tribute by editing a family member’s record.</p>
    </div>
<?php endif; ?>

<?php $this->load->view('layout/footer'); ?>
