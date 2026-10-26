<?php defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('layout/header', ['title' => isset($title) ? $title : 'Add Member']);

$member       = isset($member) ? $member : NULL;
$locked       = ! empty($structure_locked) && $member;
$upload_error = isset($upload_error) ? $upload_error : NULL;
$photo_url    = $member ? member_photo_url($member) : NULL;
$t            = clan_theme($clan->id);
$action       = $member ? site_url('clan_members/edit/' . $member->id) : site_url('clan_members/add');
$first_member = ! $member && empty($clan_has_root);   // empty clan -> this person is the Gen 1 ancestor

$input = 'block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 '
       . 'transition focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15 disabled:bg-slate-100 disabled:text-slate-500';
$label = 'mb-1.5 block text-sm font-semibold text-slate-700';
$err_o = '<p class="mt-1.5 text-xs font-medium text-rose-600">';
?>

<nav class="mb-6 text-sm text-slate-500" aria-label="Breadcrumb">
    <a href="<?= site_url('clan_members') ?>" class="font-medium transition hover:text-indigo-600">Directory</a>
    <span class="mx-2">/</span>
    <a href="<?= site_url('clan_members/clan/' . $clan->id) ?>" class="font-medium transition hover:text-indigo-600"><?= html_escape($clan->clan_name) ?></a>
    <span class="mx-2">/</span>
    <span class="font-semibold text-slate-800"><?= $member ? 'Edit member' : 'Add member' ?></span>
</nav>

<div class="mx-auto max-w-2xl">
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
        <div class="bg-gradient-to-br px-6 py-6 sm:px-8 <?= $t['gradient'] ?>">
            <h1 class="text-2xl font-bold tracking-tight text-white"><?= $member ? 'Edit ' . html_escape(member_full_name($member)) : 'Add a family member' ?></h1>
            <p class="mt-2 inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/30">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 10-8 0v4m-2 0h12a1 1 0 011 1v7a1 1 0 01-1 1H6a1 1 0 01-1-1v-7a1 1 0 011-1z"/></svg>
                Adding to <?= html_escape($clan->clan_name) ?> only
            </p>
        </div>

        <div class="p-5 sm:p-8">
            <?php if (validation_errors() || ! empty($upload_error)): ?>
                <div data-notification="error" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" role="alert">
                    Please fix the highlighted fields and try again.
                </div>
            <?php endif; ?>

            <?php if ($first_member): ?>
                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    This clan has no members yet, so this person will become the <strong>Generation 1 clan ancestor</strong>.
                </div>
            <?php endif; ?>

            <form id="member-form" data-confirm-new-member="<?= $member ? 'false' : 'true' ?>" method="post" action="<?= html_escape($action) ?>" enctype="multipart/form-data" class="space-y-5" novalidate>
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="f-first_name" class="<?= $label ?>">First name <span class="text-rose-500">*</span></label>
                        <input type="text" id="f-first_name" name="first_name" minlength="2" maxlength="100" required placeholder="e.g. Maria"
                               value="<?= set_value('first_name', $member ? $member->first_name : '') ?>" class="<?= $input ?>">
                        <?= form_error('first_name', $err_o, '</p>') ?>
                    </div>
                    <div>
                        <label for="f-last_name" class="<?= $label ?>">Last name <span class="text-rose-500">*</span></label>
                        <input type="text" id="f-last_name" name="last_name" minlength="2" maxlength="100" required placeholder="e.g. Santos"
                               value="<?= set_value('last_name', $member ? $member->last_name : '') ?>" class="<?= $input ?>">
                        <?= form_error('last_name', $err_o, '</p>') ?>
                    </div>
                </div>

                <!-- Parent + generation -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="f-parent_id" class="<?= $label ?>">Parent <?= $first_member ? '<span class="font-normal text-slate-400">(none for the ancestor)</span>' : '<span class="text-rose-500">*</span>' ?></label>
                        <?php if ($locked): ?>
                            <input type="text" disabled class="<?= $input ?>"
                                   value="<?= $member->parent_name ? html_escape($member->parent_name) : 'Clan ancestor (no parent)' ?>">
                            <p class="mt-1.5 text-xs text-slate-400">Locked: this member is the clan ancestor or already has children in the tree.</p>
                        <?php else: ?>
                            <select id="f-parent_id" name="parent_id" <?= $first_member ? '' : 'required' ?> class="<?= $input ?>">
                                <option value="" data-gen="0"><?= $first_member ? '— None (clan ancestor) —' : '— Select a parent —' ?></option>
                                <?php foreach ($parents as $p): ?>
                                    <option value="<?= (int) $p->id ?>" data-gen="<?= (int) $p->generation ?>"
                                        <?= set_select('parent_id', $p->id, $member && (int) $member->parent_id === (int) $p->id) ?>>
                                        <?= html_escape(member_full_name($p)) ?> (Gen <?= (int) $p->generation ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= form_error('parent_id', $err_o, '</p>') ?>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="f-generation" class="<?= $label ?>">Generation level</label>
                        <select id="f-generation" disabled class="<?= $input ?>">
                            <?php for ($g = 1; $g <= (int) $max_generation; $g++): ?>
                                <option value="<?= $g ?>" <?= ($member && (int) $member->generation === $g) ? 'selected' : '' ?>><?= html_escape(generation_label($g)) ?></option>
                            <?php endfor; ?>
                        </select>
                        <p class="mt-1.5 text-xs text-slate-400">Set automatically: one level below the parent.</p>
                    </div>
                </div>

                <div>
                    <label for="f-birth_date" class="<?= $label ?>">Birth date <span class="font-normal text-slate-400">(optional)</span></label>
                    <input type="date" id="f-birth_date" name="birth_date" max="<?= date('Y-m-d') ?>"
                           value="<?= set_value('birth_date', $member ? $member->birth_date : '') ?>" class="js-date-picker <?= $input ?> sm:max-w-xs">
                    <?= form_error('birth_date', $err_o, '</p>') ?>
                </div>

                <section class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4 sm:p-5" aria-labelledby="spouse-fields-title">
                    <h2 id="spouse-fields-title" class="text-sm font-bold text-slate-800">Add a spouse <span class="font-normal text-slate-500">(optional)</span></h2>
                    <p class="mt-1 text-xs text-slate-500">Add a spouse at the same time as this member. You can add more spouses later from the Spouses manager.</p>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="f-spouse_first_name" class="<?= $label ?>">Spouse first name</label>
                            <input type="text" id="f-spouse_first_name" name="spouse_first_name" maxlength="100" minlength="2"
                                   value="<?= set_value('spouse_first_name', '') ?>" class="<?= $input ?>">
                            <?= form_error('spouse_first_name', $err_o, '</p>') ?>
                        </div>
                        <div>
                            <label for="f-spouse_last_name" class="<?= $label ?>">Spouse last name</label>
                            <input type="text" id="f-spouse_last_name" name="spouse_last_name" maxlength="100" minlength="2"
                                   value="<?= set_value('spouse_last_name', '') ?>" class="<?= $input ?>">
                            <?= form_error('spouse_last_name', $err_o, '</p>') ?>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5" aria-labelledby="memorial-fields-title">
                    <h2 id="memorial-fields-title" class="text-sm font-bold text-slate-800">In memoriam</h2>
                    <p class="mt-1 text-xs text-slate-500">Mark a family member as deceased to include them on the public memorial board.</p>
                    <label class="mt-4 flex items-start gap-3">
                        <input type="checkbox" id="f-is_deceased" name="is_deceased" value="1" <?= set_checkbox('is_deceased', '1', $member && (int) $member->is_deceased === 1) ?> class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600">
                        <span class="text-sm font-semibold text-slate-700">This family member has passed away</span>
                    </label>
                    <?= form_error('is_deceased', $err_o, '</p>') ?>
                    <div id="memorial-details" class="<?= ($member && (int) $member->is_deceased === 1) || set_value('is_deceased') === '1' ? 'mt-4 space-y-4' : 'mt-4 hidden space-y-4' ?>">
                        <div>
                            <label for="f-death_date" class="<?= $label ?>">Passing date <span class="font-normal text-slate-400">(optional)</span></label>
                            <input type="date" id="f-death_date" name="death_date" max="<?= date('Y-m-d') ?>"
                                   value="<?= set_value('death_date', $member ? $member->death_date : '') ?>" class="js-date-picker <?= $input ?> sm:max-w-xs">
                            <?= form_error('death_date', $err_o, '</p>') ?>
                        </div>
                        <div>
                            <label for="f-tribute" class="<?= $label ?>">Tribute <span class="font-normal text-slate-400">(optional, up to 3,000 characters)</span></label>
                            <textarea id="f-tribute" name="tribute" rows="4" maxlength="3000" class="<?= $input ?>"><?= set_value('tribute', $member ? $member->tribute : '') ?></textarea>
                            <?= form_error('tribute', $err_o, '</p>') ?>
                        </div>
                    </div>
                </section>

                <!-- Photo -->
                <div>
                    <span class="<?= $label ?>">Photo <span class="font-normal text-slate-400">(JPG or PNG, max 2&nbsp;MB)</span></span>
                    <div class="flex flex-col items-center gap-4 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50/60 p-4 transition hover:border-slate-400 sm:flex-row">
                        <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-full bg-gradient-to-br from-slate-200 to-slate-300 ring-4 ring-white shadow">
                            <img id="photo-preview" src="<?= $photo_url ? html_escape($photo_url) : '' ?>" alt="Photo preview" class="h-full w-full object-cover <?= $photo_url ? '' : 'hidden' ?>">
                            <svg id="photo-placeholder" class="absolute inset-0 m-auto h-8 w-8 text-slate-400 <?= $photo_url ? 'hidden' : '' ?>" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.8 7.2a3.8 3.8 0 11-7.6 0 3.8 3.8 0 017.6 0zM4.5 20.1a7.5 7.5 0 0115 0"/></svg>
                        </div>
                        <div class="w-full min-w-0 flex-1 text-center sm:text-left">
                            <label for="f-photo" class="inline-flex cursor-pointer items-center rounded-lg bg-white px-3.5 py-2 text-sm font-semibold shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50 <?= $t['text'] ?>">Choose image</label>
                            <input type="file" id="f-photo" name="photo" accept="image/jpeg,image/png" class="sr-only">
                            <p id="photo-filename" class="mt-1.5 truncate text-xs text-slate-500">No file selected</p>
                        </div>
                    </div>
                    <p id="photo-error" class="mt-1.5 text-xs font-medium text-rose-600 <?= $upload_error ? '' : 'hidden' ?>"><?= html_escape($upload_error) ?></p>

                    <?php if ($photo_url): ?>
                        <label class="mt-3 flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" id="f-remove_photo" name="remove_photo" value="1" class="h-4 w-4 rounded border-slate-300">
                            Remove current photo
                        </label>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="<?= site_url('clan_members/clan/' . $clan->id) ?>" class="touch-target rounded-xl px-5 py-2.5 text-center text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">Cancel</a>
                    <button type="submit" id="form-submit" class="rounded-xl px-6 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 active:translate-y-0 <?= $t['button'] ?>">
                        <?= $member ? 'Save changes' : 'Add member' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    /* ----- Generation auto-suggest (display only; the server derives the real value) ----- */
    var parent = document.getElementById('f-parent_id');
    var gen    = document.getElementById('f-generation');
    function suggest() {
        if (!parent) { return; }
        var pg = parseInt(parent.options[parent.selectedIndex].getAttribute('data-gen'), 10) || 0;
        gen.value = String(pg > 0 ? pg + 1 : 1);
    }
    if (parent) { parent.addEventListener('change', suggest); suggest(); }

    var deceased = document.getElementById('f-is_deceased');
    var memorialDetails = document.getElementById('memorial-details');
    if (deceased && memorialDetails) {
        deceased.addEventListener('change', function () {
            memorialDetails.classList.toggle('hidden', !deceased.checked);
        });
    }

    /* ----- Photo preview + client-side checks ----- */
    var input   = document.getElementById('f-photo');
    var preview = document.getElementById('photo-preview');
    var holder  = document.getElementById('photo-placeholder');
    var nameEl  = document.getElementById('photo-filename');
    var errEl   = document.getElementById('photo-error');
    var remove  = document.getElementById('f-remove_photo');
    var MAX     = 2 * 1024 * 1024;

    function showError(msg) {
        errEl.textContent = msg;
        errEl.classList.toggle('hidden', !msg);
        if (msg && window.AppUI) { window.AppUI.notify('error', msg); }
    }

    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) { return; }
        if (['image/jpeg', 'image/png'].indexOf(file.type) === -1) { input.value = ''; showError('Only JPG and PNG images are allowed.'); return; }
        if (file.size > MAX) { input.value = ''; showError('Image is larger than 2 MB.'); return; }
        showError('');
        nameEl.textContent = file.name;
        if (remove) { remove.checked = false; }
        var reader = new FileReader();
        reader.onload = function (e) { preview.src = e.target.result; preview.classList.remove('hidden'); holder.classList.add('hidden'); };
        reader.readAsDataURL(file);
    });

})();
</script>

<?php $this->load->view('layout/footer'); ?>
