<?php defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('layout/header', ['title' => isset($title) ? $title : 'Manage children']);
$t = clan_theme($clan->id);
$input = 'block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 transition focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15';
$label = 'mb-1.5 block text-sm font-semibold text-slate-700';
?>

<nav class="mb-6 text-sm text-slate-500" aria-label="Breadcrumb">
    <a href="<?= site_url('clan_members') ?>" class="font-medium transition hover:text-indigo-600">Directory</a>
    <span class="mx-2">/</span>
    <a href="<?= site_url('clan_members/clan/' . $clan->id) ?>" class="font-medium transition hover:text-indigo-600"><?= html_escape($clan->clan_name) ?></a>
    <span class="mx-2">/</span>
    <span class="font-semibold text-slate-800">Children of <?= html_escape(member_full_name($parent)) ?></span>
</nav>

<div class="mx-auto max-w-3xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
    <div class="bg-gradient-to-br px-6 py-6 sm:px-8 <?= $t['gradient'] ?>">
        <p class="text-sm font-semibold uppercase tracking-widest text-white/70">Generation <?= (int) $generation ?></p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-white">Children of <?= html_escape(member_full_name($parent)) ?></h1>
        <p class="mt-2 text-sm text-white/85">Add several children at once. They will all be linked to this parent and added to <?= html_escape($clan->clan_name) ?>.</p>
    </div>

    <div class="space-y-7 p-5 sm:p-8">
        <?php if ($children): ?>
            <section aria-label="Current children">
                <h2 class="mb-3 text-sm font-bold text-slate-900">Current children</h2>
                <ul class="space-y-2">
                    <?php foreach ($children as $child): ?>
                        <li class="rounded-xl border border-slate-200 px-4 py-3">
                            <span class="font-semibold text-slate-800"><?= html_escape(member_full_name($child)) ?></span>
                            <span class="ml-2 text-xs text-slate-500"><?= html_escape(generation_label($child->generation)) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <section class="<?= $children ? 'border-t border-slate-100 pt-6' : '' ?>" aria-label="Add children">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Add children</h2>
                    <p class="mt-1 text-xs text-slate-500">First and last names are required. Birth dates are optional.</p>
                </div>
            </div>

            <?php if ($form_error): ?>
                <div data-notification="error" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" role="alert"><?= html_escape($form_error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= site_url('clan_members/children/' . $parent->id) ?>" class="space-y-4">
                <?= csrf_field() ?>
                <div id="child-rows" class="space-y-4">
                    <?php foreach ($rows as $index => $row): ?>
                        <fieldset class="child-row rounded-2xl border border-slate-200 p-4">
                            <legend class="px-1 text-xs font-bold text-slate-500">Child <?= (int) $index + 1 ?></legend>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="child-first-<?= (int) $index ?>" class="<?= $label ?>">First name</label>
                                    <input id="child-first-<?= (int) $index ?>" name="first_name[]" type="text" minlength="2" maxlength="100" value="<?= html_escape($row['first_name']) ?>" class="<?= $input ?>">
                                    <?php if (isset($errors[$index]['first_name'])): ?><p class="mt-1.5 text-xs font-medium text-rose-600"><?= html_escape($errors[$index]['first_name']) ?></p><?php endif; ?>
                                </div>
                                <div>
                                    <label for="child-last-<?= (int) $index ?>" class="<?= $label ?>">Last name</label>
                                    <input id="child-last-<?= (int) $index ?>" name="last_name[]" type="text" minlength="2" maxlength="100" value="<?= html_escape($row['last_name']) ?>" class="<?= $input ?>">
                                    <?php if (isset($errors[$index]['last_name'])): ?><p class="mt-1.5 text-xs font-medium text-rose-600"><?= html_escape($errors[$index]['last_name']) ?></p><?php endif; ?>
                                </div>
                                <div>
                                    <label for="child-birth-<?= (int) $index ?>" class="<?= $label ?>">Birth date <span class="font-normal text-slate-400">(optional)</span></label>
                                    <input id="child-birth-<?= (int) $index ?>" name="birth_date[]" type="date" value="<?= html_escape($row['birth_date']) ?>" class="<?= $input ?>">
                                    <?php if (isset($errors[$index]['birth_date'])): ?><p class="mt-1.5 text-xs font-medium text-rose-600"><?= html_escape($errors[$index]['birth_date']) ?></p><?php endif; ?>
                                </div>
                                <div class="flex items-end justify-end gap-2">
                                    <button type="button" class="add-child-row touch-target rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">+ Add another</button>
                                    <button type="button" class="remove-child-row rounded-lg px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 <?= count($rows) === 1 ? 'invisible' : '' ?>">Remove child</button>
                                </div>
                            </div>
                        </fieldset>
                    <?php endforeach; ?>
                </div>

                <template id="child-row-template">
                    <fieldset class="child-row rounded-2xl border border-slate-200 p-4">
                        <legend class="px-1 text-xs font-bold text-slate-500">New child</legend>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="child-first-__id__" class="<?= $label ?>">First name</label>
                                <input id="child-first-__id__" name="first_name[]" type="text" minlength="2" maxlength="100" class="<?= $input ?>">
                            </div>
                            <div>
                                <label for="child-last-__id__" class="<?= $label ?>">Last name</label>
                                <input id="child-last-__id__" name="last_name[]" type="text" minlength="2" maxlength="100" class="<?= $input ?>">
                            </div>
                            <div>
                                <label for="child-birth-__id__" class="<?= $label ?>">Birth date <span class="font-normal text-slate-400">(optional)</span></label>
                                <input id="child-birth-__id__" name="birth_date[]" type="date" class="<?= $input ?>">
                            </div>
                            <div class="flex items-end justify-end gap-2">
                                <button type="button" class="add-child-row touch-target rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">+ Add another</button>
                                <button type="button" class="remove-child-row rounded-lg px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">Remove child</button>
                            </div>
                        </div>
                    </fieldset>
                </template>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="<?= site_url('clan_members/clan/' . $clan->id) ?>#m-<?= (int) $parent->id ?>" class="touch-target rounded-xl px-5 py-2.5 text-center text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">Back to clan</a>
                    <button type="submit" class="rounded-xl px-6 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 <?= $t['button'] ?>">Save children</button>
                </div>
            </form>
        </section>
    </div>
</div>

<script>
(function () {
    var rows = document.getElementById('child-rows');
    var template = document.getElementById('child-row-template');
    var nextId = rows.querySelectorAll('.child-row').length;
    rows.addEventListener('click', function (event) {
        if (event.target.closest('.add-child-row')) {
            var row = template.content.cloneNode(true);
            row.querySelectorAll('[id], [for]').forEach(function (element) {
                if (element.id) { element.id = element.id.replace('__id__', nextId); }
                if (element.htmlFor) { element.htmlFor = element.htmlFor.replace('__id__', nextId); }
            });
            nextId++;
            rows.appendChild(row);
            rows.lastElementChild.querySelector('input').focus();
            return;
        }
        if (event.target.closest('.remove-child-row') && rows.querySelectorAll('.child-row').length > 1) {
            event.target.closest('.child-row').remove();
        }
    });
})();
</script>

<?php $this->load->view('layout/footer'); ?>
