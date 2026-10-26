(function () {
    'use strict';

    function notify(type, message) {
        if (!window.toastr) { return false; }
        window.toastr[type](message, '', type === 'error' ? {timeOut: 0, extendedTimeOut: 0} : {});
        return true;
    }
    window.AppUI = {notify: notify};

    function validateDate(field) {
        field.setCustomValidity('');
        if (field.id === 'f-death_date' && !document.getElementById('f-is_deceased').checked) { return; }
        if (!field.value || field.type !== 'text') { return; }
        var datetime = field.classList.contains('js-datetime-picker');
        var value = field.value.trim();
        var match = datetime ? /^(\d{4}-\d{2}-\d{2})[ T](\d{2}):(\d{2})$/.exec(value) : /^(\d{4}-\d{2}-\d{2})$/.exec(value);
        try {
            if (!match) { throw new Error('format'); }
            window.jQuery.datepicker.parseDate('yy-mm-dd', match[1]);
            if (datetime && (+match[2] > 23 || +match[3] > 59)) { throw new Error('time'); }
            if (field.max && match[1] > field.max) {
                field.setCustomValidity('Please choose a date on or before ' + field.max + '.');
            }
        } catch (error) {
            field.setCustomValidity(datetime ? 'Please choose a valid date and time.' : 'Please choose a valid date.');
        }
    }

    function validateForm(form) {
        form.querySelectorAll('.js-date-picker, .js-datetime-picker').forEach(validateDate);
        var start = form.querySelector('#event-start');
        var end = form.querySelector('#event-end');
        if (start && end && start.value && end.value && !start.validationMessage && !end.validationMessage && end.value.trim().replace('T', ' ') < start.value.trim().replace('T', ' ')) {
            end.setCustomValidity('The end date and time must be on or after the start.');
        }
        if (!form.reportValidity()) {
            notify('error', 'Please check the highlighted fields before continuing.');
            return false;
        }
        return true;
    }

    function initPickers() {
        var $ = window.jQuery;
        if (!$ || !$.fn.datepicker) { return; }
        document.querySelectorAll('.js-date-picker, .js-datetime-picker').forEach(function (field) {
            var datetime = field.classList.contains('js-datetime-picker');
            if (datetime && !$.fn.datetimepicker) { return; }
            var value = field.value;
            field.type = 'text';
            field.autocomplete = 'off';
            if (window.matchMedia('(pointer: coarse)').matches) { field.inputMode = 'none'; }
            var options = {
                dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true,
                yearRange: 'c-250:c+20', showButtonPanel: true, showAnim: '',
                onSelect: function () { field.setCustomValidity(''); field.dispatchEvent(new Event('change', {bubbles: true})); },
                onUpdateDatepicker: function (instance) {
                    if (field.required || instance.dpDiv.find('.ui-datepicker-clear').length) { return; }
                    $('<button type="button" class="ui-datepicker-clear ui-state-default ui-priority-secondary ui-corner-all">Clear</button>')
                        .on('click', function () {
                            $(field)[datetime ? 'datetimepicker' : 'datepicker']('setDate', null);
                            field.value = '';
                            field.setCustomValidity('');
                            field.dispatchEvent(new Event('change', {bubbles: true}));
                            $(field).datepicker('hide');
                        }).appendTo(instance.dpDiv.find('.ui-datepicker-buttonpane'));
                }
            };
            if (field.max) { options.maxDate = $.datepicker.parseDate('yy-mm-dd', field.max); }
            if (datetime) {
                options.timeFormat = 'HH:mm';
                options.separator = ' ';
                options.controlType = 'select';
                options.oneLine = true;
                $(field).datetimepicker(options);
                field.value = value.replace('T', ' ');
            } else { $(field).datepicker(options); field.value = value; }
            field.addEventListener('click', function () { $(field)[datetime ? 'datetimepicker' : 'datepicker']('show'); });
            field.addEventListener('input', function () {
                field.setCustomValidity('');
                // Synchronize pasted values with the add-on's internal date and time.
                if (datetime) { $(field).triggerHandler('keyup'); }
            });
            field.addEventListener('change', function () { validateDate(field); });
        });
        document.querySelectorAll('form').forEach(function (form) {
            if (!form.querySelector('.js-datetime-picker')) { return; }
            var start = form.querySelector('#event-start');
            var end = form.querySelector('#event-end');
            if (start && end) {
                ['input', 'change'].forEach(function (event) { start.addEventListener(event, function () { end.setCustomValidity(''); }); });
            }
        });
    }

    function initForms() {
        var pendingForm = null;
        var posting = false;
        var resets = [];
        document.querySelectorAll('form').forEach(function (form) {
            var submitting = false;
            var submitter = null;
            var submittedButton = null;
            var buttons = Array.prototype.map.call(form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]'), function (button) {
                return {element: button, html: button.innerHTML, value: button.value, disabled: button.disabled, label: button.getAttribute('aria-label')};
            });
            form.noValidate = true;

            function perform() {
                pendingForm = null;
                if (submitting || posting || !validateForm(form)) { return; }
                if (form.getAttribute('data-submit-mode') === 'client') {
                    form.dispatchEvent(new Event('app:confirmed-submit'));
                    return;
                }
                submitting = true;
                posting = true;
                form.querySelectorAll('.js-datetime-picker').forEach(function (field) { field.value = field.value.trim().replace(' ', 'T'); });
                // Native submit() retains uploads and CSRF fields; include the clicked button's value as well.
                if (submitter && submitter.name) {
                    submittedButton = document.createElement('input');
                    submittedButton.type = 'hidden';
                    submittedButton.name = submitter.name;
                    submittedButton.value = submitter.value;
                    form.appendChild(submittedButton);
                }
                var busyLabel = form.getAttribute('data-submit-label') || 'Submitting…';
                buttons.forEach(function (saved) {
                    var button = saved.element;
                    button.disabled = true;
                    if (button.tagName === 'INPUT') { button.value = busyLabel; }
                    else if (button.textContent.trim()) { button.textContent = busyLabel; }
                    else { button.setAttribute('aria-label', busyLabel); }
                    button.classList.add('opacity-70', 'cursor-wait');
                });
                form.setAttribute('aria-busy', 'true');
                HTMLFormElement.prototype.submit.call(form);
            }

            function finish(confirmed) {
                if (pendingForm !== form) { return; }
                pendingForm = null;
                if (confirmed) { perform(); }
                else { notify('info', 'Action canceled.'); }
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                if (pendingForm || submitting || posting || !validateForm(form)) { return; }
                submitter = event.submitter || null;
                pendingForm = form;
                var text = form.getAttribute('data-confirm-text') || 'Continue with this action?';
                var summary = (form.getAttribute('data-confirm-fields') || '').split(/\s+/).filter(Boolean).map(function (name) {
                    var field = form.elements.namedItem(name);
                    if (!field || field.type === 'password') { return ''; }
                    return field.tagName === 'SELECT' ? field.options[field.selectedIndex].text : field.value.trim();
                }).filter(Boolean).join(' ');
                if (summary) { text += '\n\n' + summary; }
                var title = form.getAttribute('data-confirm-title') || 'Submit this form?';
                if (!window.Swal) { finish(window.confirm(title + '\n\n' + text)); return; }
                var danger = form.getAttribute('data-confirm-danger') === 'true';
                window.Swal.fire({
                    title: title, text: text, icon: danger ? 'warning' : 'question',
                    showCancelButton: true, confirmButtonText: form.getAttribute('data-confirm-button') || 'Continue',
                    cancelButtonText: form.getAttribute('data-cancel-button') || 'Cancel',
                    confirmButtonColor: danger ? '#e11d48' : '#4f46e5',
                    reverseButtons: true, focusCancel: true, heightAuto: false
                }).then(function (result) { finish(result.isConfirmed); });
            });

            resets.push(function () {
                submitting = false;
                if (submittedButton) { submittedButton.remove(); submittedButton = null; }
                buttons.forEach(function (saved) {
                    var button = saved.element;
                    button.disabled = saved.disabled;
                    if (button.tagName === 'INPUT') { button.value = saved.value; }
                    else { button.innerHTML = saved.html; }
                    if (saved.label === null) { button.removeAttribute('aria-label'); }
                    else { button.setAttribute('aria-label', saved.label); }
                    button.classList.remove('opacity-70', 'cursor-wait');
                });
                form.removeAttribute('aria-busy');
                form.querySelectorAll('.js-datetime-picker').forEach(function (field) { if (field.type === 'text') { field.value = field.value.replace('T', ' '); } });
            });
        });
        window.AppUI.formsReady = true;
        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) { return; }
            pendingForm = null;
            posting = false;
            if (window.Swal) { window.Swal.close(); }
            resets.forEach(function (reset) { reset(); });
        });
    }

    function init() {
        if (window.toastr) {
            window.toastr.options = {
                closeButton: true, progressBar: true, escapeHtml: true, preventDuplicates: true,
                positionClass: 'toast-top-right', timeOut: 6500, extendedTimeOut: 2000
            };
            document.querySelectorAll('[data-notification]').forEach(function (notice) {
                if (notify(notice.getAttribute('data-notification'), notice.textContent.trim())) { notice.hidden = true; }
            });
        }
        initPickers();
        initForms();
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); }
    else { init(); }
})();
