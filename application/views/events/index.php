<?php defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('layout/header', ['title' => isset($title) ? $title : 'Clan Calendar']);
$categories = [
    'reunion'     => ['label' => 'Reunion / gathering', 'dot' => 'bg-rose-500', 'badge' => 'bg-rose-50 text-rose-700 ring-rose-200'],
    'celebration' => ['label' => 'Birthday / celebration', 'dot' => 'bg-sky-500', 'badge' => 'bg-sky-50 text-sky-700 ring-sky-200'],
    'memorial'    => ['label' => 'Necrological / interment', 'dot' => 'bg-slate-700', 'badge' => 'bg-slate-100 text-slate-700 ring-slate-300'],
];
$headcounts = [];
foreach ($events as $event)
{
    foreach ($event->rsvps as $rsvp)
    {
        $headcounts[(int) $event->id][(int) $rsvp->clan_id] = (int) $rsvp->headcount;
    }
}
?>

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <nav class="mb-2 text-sm text-slate-500" aria-label="Breadcrumb"><a href="<?= site_url('clan_members') ?>" class="font-medium hover:text-indigo-600">Directory</a><span class="mx-2">/</span><span class="font-semibold text-slate-800">Clan Calendar</span></nav>
        <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Plan together</p>
        <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Clan Calendar</h1>
        <p class="mt-2 text-sm text-slate-500">Reunions, celebrations, and memorial events across all clans.</p>
    </div>
    <?php if ($rep): ?>
        <a href="<?= site_url('events/create') ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-indigo-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
            Create event
        </a>
    <?php endif; ?>
</div>

<section class="mb-8 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" aria-label="Monthly calendar">
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex w-full items-center justify-between gap-2 sm:w-auto">
            <button type="button" id="calendar-prev" aria-label="Previous month" class="rounded-lg p-2 text-slate-600 transition hover:bg-slate-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>
            </button>
            <h2 id="calendar-month" aria-live="polite" class="min-w-0 flex-1 text-center text-base font-bold text-slate-900 sm:min-w-40 sm:text-lg"></h2>
            <button type="button" id="calendar-next" aria-label="Next month" class="rounded-lg p-2 text-slate-600 transition hover:bg-slate-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
            </button>
        </div>
        <div class="flex flex-wrap gap-x-3 gap-y-2 text-xs font-medium text-slate-600 lg:justify-end">
            <?php foreach ($categories as $category): ?>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full <?= $category['dot'] ?>"></span><?= html_escape($category['label']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <p class="mb-3 text-xs text-slate-500 sm:hidden">Tap a marked date to see its events below.</p>
    <div class="grid grid-cols-7 border-l border-t border-slate-200" id="calendar-grid"></div>
</section>

<section aria-labelledby="event-list-title">
    <div class="mb-4 flex items-center justify-between">
        <h2 id="event-list-title" class="text-xl font-bold tracking-tight text-slate-900">All events</h2>
        <span class="text-sm text-slate-500"><?= count($events) ?> total</span>
    </div>
    <?php if ( ! $events): ?>
        <div class="rounded-3xl border-2 border-dashed border-slate-300 bg-white px-6 py-14 text-center">
            <p class="text-lg font-bold text-slate-800">No events scheduled</p>
            <p class="mt-1 text-sm text-slate-500"><?= $rep ? 'Create the first event for your clan or all clans.' : 'Check back later for upcoming family events.' ?></p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($events as $event):
                $category = isset($categories[$event->category]) ? $categories[$event->category] : $categories['reunion'];
                $can_rsvp = $rep && ($event->clan_id === NULL || (int) $event->clan_id === (int) $rep->clan_id);
                $can_manage = $rep && (int) $event->created_by_clan_id === (int) $rep->clan_id;
                $my_headcount = isset($headcounts[(int) $event->id][(int) ($rep ? $rep->clan_id : 0)]) ? $headcounts[(int) $event->id][(int) $rep->clan_id] : '';
                $date = new DateTime($event->starts_at);
            ?>
                <article id="event-<?= (int) $event->id ?>" data-event-date="<?= html_escape($date->format('Y-m-d')) ?>" class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-6">
                        <div class="flex min-w-0 gap-4">
                            <div class="flex w-12 shrink-0 flex-col items-center justify-center self-start rounded-xl bg-slate-50 py-2 text-slate-700 sm:w-16">
                                <span class="text-xs font-bold uppercase"><?= html_escape($date->format('M')) ?></span>
                                <span class="text-2xl font-bold leading-tight"><?= html_escape($date->format('j')) ?></span>
                            </div>
                            <div class="min-w-0">
                                <span class="inline-flex max-w-full items-center gap-1.5 rounded-xl px-2.5 py-1 text-xs font-semibold ring-1 sm:rounded-full <?= $category['badge'] ?>">
                                    <span class="h-2 w-2 shrink-0 rounded-full <?= $category['dot'] ?>"></span><?= html_escape($category['label']) ?>
                                </span>
                                <h3 class="mt-2 text-lg font-bold text-slate-900"><?= html_escape($event->title) ?></h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    <?= html_escape($date->format('l, F j, Y · g:i A')) ?>
                                    <?php if ($event->ends_at): ?> – <?= html_escape((new DateTime($event->ends_at))->format('g:i A')) ?><?php endif; ?>
                                    · <?= $event->clan_id === NULL ? 'All clans' : html_escape($event->scope_clan_name) ?>
                                </p>
                                <?php if ($event->location): ?><p class="mt-1 text-sm text-slate-600"><?= html_escape($event->location) ?></p><?php endif; ?>
                                <?php if ($event->description): ?><p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-600"><?= html_escape($event->description) ?></p><?php endif; ?>
                            </div>
                        </div>
                        <?php if ($can_manage): ?>
                            <div class="flex shrink-0 gap-2 sm:justify-end">
                                <a href="<?= site_url('events/edit/' . $event->id) ?>" class="touch-target rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200">Edit</a>
                                <form data-confirm-title="Delete this event?" data-confirm-text="<?= html_escape($event->title . ' and all branch headcounts will be permanently removed.') ?>" data-confirm-danger="true" data-confirm-button="Delete event" data-submit-label="Deleting…" method="post" action="<?= site_url('events/delete/' . $event->id) ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Delete</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:px-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h4 class="text-sm font-bold text-slate-800">Branch headcount</h4>
                            <span class="text-sm font-semibold text-slate-700"><?= (int) $event->total_headcount ?> attending total</span>
                        </div>
                        <?php if ($event->rsvps): ?>
                            <ul class="mt-3 flex flex-wrap gap-2">
                                <?php foreach ($event->rsvps as $rsvp): ?>
                                    <li class="rounded-full bg-white px-3 py-1.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200"><?= html_escape($rsvp->clan_name) ?>: <strong><?= (int) $rsvp->headcount ?></strong></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="mt-2 text-xs text-slate-500">No clan headcounts submitted yet.</p>
                        <?php endif; ?>

                        <?php if ($can_rsvp): ?>
                            <form data-confirm-title="Save headcount?" data-confirm-text="Save this attendee count for your clan?" data-confirm-fields="headcount" data-confirm-button="Save headcount" data-submit-label="Saving…" method="post" action="<?= site_url('events/rsvp/' . $event->id) ?>" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                                <?= csrf_field() ?>
                                <div>
                                    <label for="headcount-<?= (int) $event->id ?>" class="mb-1 block text-xs font-semibold text-slate-600"><?= html_escape($rep->clan_name) ?> attendees</label>
                                    <input id="headcount-<?= (int) $event->id ?>" name="headcount" type="number" min="0" max="10000" step="1" required value="<?= html_escape((string) $my_headcount) ?>" placeholder="Enter headcount" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm sm:w-44">
                                </div>
                                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">Save headcount</button>
                            </form>
                        <?php elseif ( ! $rep && $event->clan_id === NULL): ?>
                            <p class="mt-3 text-xs text-slate-500"><a href="<?= site_url('login') ?>" class="font-semibold text-indigo-600 hover:text-indigo-700">Sign in as a representative</a> to submit your branch headcount.</p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script>
(function () {
    var events = <?= json_encode(array_map(function ($event) {
        return ['id' => (int) $event->id, 'title' => $event->title, 'date' => substr($event->starts_at, 0, 10), 'category' => $event->category];
    }, $events), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var colors = {reunion: 'bg-rose-500', celebration: 'bg-sky-500', memorial: 'bg-slate-700'};
    var monthHeading = document.getElementById('calendar-month');
    var grid = document.getElementById('calendar-grid');
    var monthDate = new Date();
    monthDate.setDate(1);

    function renderCalendar() {
        var year = monthDate.getFullYear();
        var month = monthDate.getMonth();
        monthHeading.textContent = monthDate.toLocaleDateString(undefined, {month: 'long', year: 'numeric'});
        grid.textContent = '';
        ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(function (day) {
            var heading = document.createElement('div');
            heading.className = 'border-b border-r border-slate-200 bg-slate-50 px-1 py-2 text-center text-[10px] font-bold uppercase text-slate-500 sm:px-2 sm:text-xs';
            heading.textContent = day;
            grid.appendChild(heading);
        });

        var firstWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        for (var blank = 0; blank < firstWeekday; blank++) {
            var empty = document.createElement('div');
            empty.className = 'min-w-0 min-h-20 border-b border-r border-slate-200 bg-slate-50/50 sm:min-h-28';
            grid.appendChild(empty);
        }
        for (var day = 1; day <= daysInMonth; day++) {
            var dayKey = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
            var cell = document.createElement('div');
            cell.className = 'min-w-0 min-h-20 border-b border-r border-slate-200 p-1 sm:min-h-28 sm:p-2';
            var dayEvents = events.filter(function (event) { return event.date === dayKey; });
            var number = document.createElement('span');
            number.className = (dayEvents.length ? 'hidden sm:inline ' : '') + 'text-xs font-semibold text-slate-700';
            number.textContent = day;
            cell.appendChild(number);

            if (dayEvents.length) {
                var dayLink = document.createElement('a');
                dayLink.href = '#event-' + dayEvents[0].id;
                dayLink.className = 'flex min-h-16 flex-col items-center gap-2 rounded-lg bg-indigo-50 py-1 text-xs font-bold text-indigo-900 hover:bg-indigo-100 sm:hidden';
                dayLink.setAttribute('aria-label', new Date(year, month, day).toLocaleDateString(undefined, {month: 'long', day: 'numeric', year: 'numeric'}) + ': ' + dayEvents.map(function (event) { return event.title; }).join('; '));
                var dayNumber = document.createElement('span');
                dayNumber.textContent = day;
                dayLink.appendChild(dayNumber);
                var dots = document.createElement('span');
                dots.className = 'flex flex-wrap justify-center gap-0.5';
                dots.setAttribute('aria-hidden', 'true');
                dayEvents.slice(0, 3).forEach(function (event) {
                    var marker = document.createElement('span');
                    marker.className = 'h-1.5 w-1.5 rounded-full ' + (colors[event.category] || 'bg-indigo-500');
                    dots.appendChild(marker);
                });
                dayLink.appendChild(dots);
                if (dayEvents.length > 3) {
                    var more = document.createElement('span');
                    more.textContent = '+' + (dayEvents.length - 3);
                    more.className = 'text-[10px]';
                    dayLink.appendChild(more);
                }
                cell.appendChild(dayLink);
            }

            dayEvents.forEach(function (event) {
                var link = document.createElement('a');
                link.href = '#event-' + event.id;
                link.className = 'mt-1 hidden min-w-0 items-start gap-1 rounded-md bg-slate-50 px-1.5 py-1 text-left text-xs leading-tight text-slate-700 hover:bg-slate-100 sm:flex';
                link.setAttribute('aria-label', event.title);
                link.title = event.title;
                var dot = document.createElement('span');
                dot.className = 'mt-0.5 h-2 w-2 shrink-0 rounded-full ' + (colors[event.category] || 'bg-indigo-500');
                link.appendChild(dot);
                var label = document.createElement('span');
                label.className = 'min-w-0 break-words line-clamp-2';
                label.textContent = event.title;
                link.appendChild(label);
                cell.appendChild(link);
            });
            grid.appendChild(cell);
        }
    }

    document.getElementById('calendar-prev').addEventListener('click', function () {
        monthDate.setMonth(monthDate.getMonth() - 1);
        renderCalendar();
    });
    document.getElementById('calendar-next').addEventListener('click', function () {
        monthDate.setMonth(monthDate.getMonth() + 1);
        renderCalendar();
    });
    renderCalendar();
})();
</script>

<?php $this->load->view('layout/footer'); ?>
