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
            notify('error', 'Please check the highlighted fields before saving.');
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
            form.addEventListener('submit', function (event) {
                if (!validateForm(form)) { event.preventDefault(); return; }
                form.querySelectorAll('.js-datetime-picker').forEach(function (field) { field.value = field.value.trim().replace(' ', 'T'); });
            });
            var start = form.querySelector('#event-start');
            var end = form.querySelector('#event-end');
            if (start && end) {
                ['input', 'change'].forEach(function (event) { start.addEventListener(event, function () { end.setCustomValidity(''); }); });
            }
        });
    }

    function initMemberForm() {
        var form = document.getElementById('member-form');
        if (!form) { return; }
        var button = document.getElementById('form-submit');
        var buttonText = button.textContent;
        var confirming = false;
        var submitting = false;

        function save() {
            confirming = false;
            if (submitting || !validateForm(form)) { return; }
            submitting = true;
            button.disabled = true;
            button.textContent = 'Saving…';
            form.setAttribute('aria-busy', 'true');
            button.classList.add('opacity-70', 'cursor-wait');
            HTMLFormElement.prototype.submit.call(form);
        }
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (confirming || submitting || !validateForm(form)) { return; }
            if (form.getAttribute('data-confirm-new-member') !== 'true') { save(); return; }
            confirming = true;
            var name = document.getElementById('f-first_name').value.trim() + ' ' + document.getElementById('f-last_name').value.trim();
            if (!window.Swal) {
                confirming = false;
                if (window.confirm('Add ' + name + ' to your family tree?')) { save(); }
                return;
            }
            window.Swal.fire({
                title: 'Save new family member?', text: 'Add ' + name + ' to your family tree?',
                icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, save member',
                cancelButtonText: 'Keep editing', confirmButtonColor: '#4f46e5',
                reverseButtons: true, focusCancel: true, heightAuto: false
            }).then(function (result) {
                confirming = false;
                if (result.isConfirmed) { save(); }
            });
        });
        window.addEventListener('pageshow', function () {
            confirming = false; submitting = false;
            button.disabled = false; button.textContent = buttonText;
            button.classList.remove('opacity-70', 'cursor-wait');
            form.removeAttribute('aria-busy');
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
        initMemberForm();
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); }
    else { init(); }
})();
