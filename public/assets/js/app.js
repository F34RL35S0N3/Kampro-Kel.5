/* ==========================================================================
   SIMLAP-Riset — progressive enhancement
   Semua fitur inti tetap berfungsi tanpa JavaScript.
   ========================================================================== */
(function () {
    'use strict';

    document.documentElement.classList.add('js');
    document.body.classList.add('js-ready');

    var $ = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

    /* ---------------------------------------------------------------------
       Dialog konfirmasi (batalkan pengisian)
       --------------------------------------------------------------------- */
    function initDialogs() {
        $$('[data-dialog-open]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                var dialog = $(trigger.getAttribute('data-dialog-open'));
                if (dialog && typeof dialog.showModal === 'function') {
                    dialog.showModal();
                }
            });
        });

        $$('dialog').forEach(function (dialog) {
            dialog.addEventListener('click', function (event) {
                if (event.target === dialog) {
                    dialog.close();
                }
            });
        });

        $$('[data-dialog-close]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var dialog = btn.closest('dialog');
                if (dialog) {
                    dialog.close();
                }
            });
        });
    }

    /* ---------------------------------------------------------------------
       Tabel dinamis (anggota / luaran)
       --------------------------------------------------------------------- */
    function reindex(container) {
        var rows = $$('[data-row]', container);
        rows.forEach(function (row, index) {
            var number = row.querySelector('[data-row-index]');
            if (number) {
                number.textContent = String(index + 1);
            }

            $$('[name]', row).forEach(function (input) {
                input.name = input.name.replace(/\[\d+\]/g, '[' + index + ']');
            });

            $$('[id]', row).forEach(function (el) {
                el.id = el.id.replace(/_\d+_/g, '_' + index + '_');
            });

            $$('label[for]', row).forEach(function (label) {
                label.htmlFor = label.htmlFor.replace(/_\d+_/g, '_' + index + '_');
            });

            $$('[aria-describedby]', row).forEach(function (el) {
                el.setAttribute('aria-describedby', el.getAttribute('aria-describedby').replace(/-\d+-/g, '-' + index + '-'));
            });

            $$('.field-error[id]', row).forEach(function (el) {
                el.id = el.id.replace(/-\d+-/g, '-' + index + '-');
            });
        });

        var removeButtons = $$('[data-remove-row]', container);
        removeButtons.forEach(function (btn) {
            btn.disabled = rows.length <= 1;
        });

        var counter = $('[data-counter]', container);
        var max = parseInt(container.getAttribute('data-max') || '10', 10);
        if (counter) {
            counter.textContent = rows.length + ' / ' + max + ' baris';
        }

        var addButton = $('[data-add-row]', container);
        if (addButton) {
            addButton.disabled = rows.length >= max;
            addButton.classList.toggle('is-disabled', rows.length >= max);
        }
    }

    function initDynamicTable(container) {
        var name = container.getAttribute('data-dynamic');
        var template = document.getElementById('row-template-' + name);
        var body = $('[data-rows]', container);
        var max = parseInt(container.getAttribute('data-max') || '10', 10);
        if (!template || !body) {
            return;
        }

        container.addEventListener('click', function (event) {
            var add = event.target.closest('[data-add-row]');
            if (add) {
                var count = $$('[data-row]', body).length;
                if (count >= max) {
                    return;
                }
                var html = template.innerHTML.replace(/__I__/g, String(count)).replace(/__N__/g, String(count + 1));
                var wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                var row = wrapper.firstElementChild;
                if (!row) {
                    return;
                }
                body.appendChild(row);
                reindex(container);
                var firstField = $('input, select, textarea', row);
                if (firstField) {
                    firstField.focus();
                }
                return;
            }

            var remove = event.target.closest('[data-remove-row]');
            if (remove) {
                var rows = $$('[data-row]', body);
                if (rows.length <= 1) {
                    return;
                }
                var target = remove.closest('[data-row]');
                if (target) {
                    target.remove();
                    reindex(container);
                }
            }
        });

        reindex(container);
    }

    function initDynamicTables() {
        $$('[data-dynamic]').forEach(initDynamicTable);
    }

    /* ---------------------------------------------------------------------
       Format input
       --------------------------------------------------------------------- */
    function formatRupiah(value) {
        var digits = String(value).replace(/\D/g, '');
        if (!digits) {
            return '';
        }
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function initRupiah() {
        $$('[data-rupiah]').forEach(function (input) {
            var preview = input.closest('.field') ? $('[data-rupiah-preview]', input.closest('.field')) : null;
            var sync = function () {
                var formatted = formatRupiah(input.value);
                input.value = formatted;
                if (preview) {
                    var digits = formatted.replace(/\D/g, '');
                    preview.textContent = digits
                        ? 'Nilai: Rp ' + formatted
                        : 'Masukkan angka rupiah bulat (1 – 5.000.000.000).';
                }
            };
            input.addEventListener('input', sync);
            sync();
        });
    }

    function initDigits() {
        $$('[data-digits]').forEach(function (input) {
            input.addEventListener('input', function () {
                var cleaned = input.value.replace(/\D/g, '');
                if (cleaned !== input.value) {
                    input.value = cleaned;
                }
                var length = parseInt(input.getAttribute('data-digits-length') || '0', 10);
                if (length > 0) {
                    input.setCustomValidity(
                        cleaned.length === 0 || cleaned.length === length
                            ? ''
                            : 'Harus tepat ' + length + ' digit angka.'
                    );
                }
            });
        });
    }

    function initPhone() {
        $$('[data-phone]').forEach(function (input) {
            input.addEventListener('input', function () {
                input.value = input.value.replace(/[^\d+\s().-]/g, '');
                var digits = input.value.replace(/\D/g, '');
                input.setCustomValidity(
                    digits.length === 0 || (digits.length >= 9 && digits.length <= 15)
                        ? ''
                        : 'Nomor telepon harus 9–15 digit.'
                );
            });
        });
    }

    function initCounters() {
        $$('[data-counter-for]').forEach(function (output) {
            var input = document.getElementById(output.getAttribute('data-counter-for'));
            if (!input) {
                return;
            }
            var max = parseInt(input.getAttribute('maxlength') || '0', 10);
            var update = function () {
                output.textContent = max > 0
                    ? input.value.length + ' / ' + max
                    : String(input.value.length);
            };
            input.addEventListener('input', update);
            update();
        });
    }

    /* ---------------------------------------------------------------------
       Interaksi umum
       --------------------------------------------------------------------- */
    function initPasswordToggles() {
        $$('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = btn.closest('.password-wrap') ? $('input', btn.closest('.password-wrap')) : null;
                if (!input) {
                    return;
                }
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.textContent = show ? 'Sembunyi' : 'Lihat';
                btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
            });
        });
    }

    function initCopyButtons() {
        $$('[data-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = btn.getAttribute('data-copy') || '';
                var done = function () {
                    var original = btn.textContent;
                    btn.textContent = 'Tersalin';
                    window.setTimeout(function () { btn.textContent = original; }, 1600);
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(done).catch(function () {});
                    return;
                }
                var temp = document.createElement('textarea');
                temp.value = text;
                temp.setAttribute('readonly', '');
                temp.style.position = 'absolute';
                temp.style.left = '-9999px';
                document.body.appendChild(temp);
                temp.select();
                try { document.execCommand('copy'); done(); } catch (e) {}
                document.body.removeChild(temp);
            });
        });
    }

    function initPrintButtons() {
        $$('[data-print]').forEach(function (btn) {
            btn.addEventListener('click', function () { window.print(); });
        });
    }

    function initCountdowns() {
        $$('[data-countdown]').forEach(function (el) {
            var remaining = parseInt(el.getAttribute('data-countdown') || '0', 10);
            if (!remaining || remaining < 1) {
                return;
            }
            var timer = window.setInterval(function () {
                remaining -= 1;
                el.textContent = String(Math.max(remaining, 0));
                if (remaining <= 0) {
                    window.clearInterval(timer);
                    var hint = el.closest('.error-retry');
                    if (hint) {
                        hint.textContent = 'Anda sudah dapat mencoba kembali.';
                    }
                }
            }, 1000);
        });
    }

    /* ---------------------------------------------------------------------
       Formulir wizard
       --------------------------------------------------------------------- */
    function clearFieldError(input) {
        input.removeAttribute('aria-invalid');
        var field = input.closest('.field');
        if (field) {
            field.classList.remove('field--error');
        }
        var rowField = input.closest('.dynamic-row__field');
        if (rowField) {
            var msg = rowField.querySelector('.field-error');
            if (msg) {
                msg.remove();
            }
        }
        input.setCustomValidity('');
    }

    function pruneEmptyRows(form) {
        $$('[data-row]', form).forEach(function (row) {
            var inputs = $$('input:not([type="hidden"]), select, textarea', row);
            if (inputs.length === 0) {
                return;
            }
            var allEmpty = inputs.every(function (input) {
                return String(input.value).trim() === '';
            });
            if (allEmpty) {
                var container = row.closest('[data-dynamic]');
                var total = container ? $$('[data-row]', container).length : 0;
                if (total > 1) {
                    row.remove();
                }
            }
        });
        $$('[data-dynamic]').forEach(function (container) {
            var body = $('[data-rows]', container);
            if (body) {
                reindex(container);
            }
        });
    }

    function validateForm(form) {
        pruneEmptyRows(form);

        $$('[data-digits][data-digits-length]', form).forEach(function (input) {
            var length = parseInt(input.getAttribute('data-digits-length') || '0', 10);
            var digits = input.value.replace(/\D/g, '');
            input.setCustomValidity(
                digits.length === 0 || digits.length === length ? '' : 'Harus tepat ' + length + ' digit angka.'
            );
        });

        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            if (typeof form.reportValidity === 'function') {
                form.reportValidity();
            }
            var firstInvalid = $(':invalid', form);
            if (firstInvalid) {
                firstInvalid.focus();
                if (firstInvalid.scrollIntoView) {
                    firstInvalid.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
            }
            return false;
        }
        return true;
    }

    function initWizardForm(form) {
        form.addEventListener('input', function (event) {
            if (event.target.matches('input, textarea, select')) {
                clearFieldError(event.target);
            }
        });

        form.addEventListener('change', function (event) {
            if (event.target.matches('input, textarea, select')) {
                clearFieldError(event.target);
            }
        });

        form.addEventListener('submit', function (event) {
            if (!validateForm(form)) {
                event.preventDefault();
                return;
            }
            var submit = form.querySelector('button[type="submit"]');
            if (submit) {
                submit.disabled = true;
                submit.classList.add('is-disabled');
                submit.setAttribute('aria-busy', 'true');
                var label = submit.querySelector('[data-submit-label]');
                if (label) {
                    label.textContent = 'Memproses…';
                } else {
                    submit.textContent = 'Memproses…';
                }
            }
            form.setAttribute('aria-busy', 'true');
        });
    }

    function initWizardForms() {
        $$('[data-wizard-form]').forEach(initWizardForm);
    }

    /* ---------------------------------------------------------------------
       Inisialisasi
       --------------------------------------------------------------------- */
    function init() {
        initDialogs();
        initDynamicTables();
        initRupiah();
        initDigits();
        initPhone();
        initCounters();
        initPasswordToggles();
        initCopyButtons();
        initPrintButtons();
        initCountdowns();
        initWizardForms();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
