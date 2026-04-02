// Enhanced client-side validation with popup toasts and inline field errors.

/**
 * Show a toast notification summarising missing/invalid fields.
 */
function showToast(messages, type) {
    type = type || 'error';
    var container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.setAttribute('aria-live', 'assertive');
        document.body.appendChild(container);
    }

    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.setAttribute('role', 'alert');

    var icon = type === 'error' ? '✕' : type === 'warning' ? '⚠' : '✓';
    var list = messages.map(function (m) { return '<li>' + m + '</li>'; }).join('');
    toast.innerHTML = '<span class="toast-icon">' + icon + '</span>'
        + '<div class="toast-body"><ul>' + list + '</ul></div>'
        + '<button class="toast-close" aria-label="Dismiss" onclick="this.parentElement.remove()">×</button>';

    container.appendChild(toast);

    // Auto-dismiss after 7s.
    setTimeout(function () {
        toast.style.transition = 'opacity .4s ease, transform .4s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(16px)';
        setTimeout(function () { toast.remove(); }, 430);
    }, 7000);
}

/**
 * Clear inline errors added by this script.
 */
function clearInlineErrors(form) {
    form.querySelectorAll('.js-field-error').forEach(function (el) { el.remove(); });
    form.querySelectorAll('.input-error-js').forEach(function (el) {
        el.classList.remove('input-error-js');
    });
}

/**
 * Add an inline error message beneath a field.
 */
function addInlineError(field, message) {
    field.classList.add('input-error-js');
    var span = document.createElement('span');
    span.className = 'field-error js-field-error';
    span.textContent = message;
    // Insert after the field (or its parent label).
    var parent = field.closest('label') || field.parentNode;
    parent.appendChild(span);
}

/**
 * Validate a single field; return error message or null.
 */
function validateField(field) {
    var val = field.value.trim();
    var label = (field.closest('label') && field.closest('label').childNodes[0])
        ? field.closest('label').childNodes[0].textContent.trim().replace(/\s*\*\s*$/, '').trim()
        : (field.name || field.id || 'This field');

    if (field.hasAttribute('required') && val === '') {
        return label + ' is required.';
    }
    if (field.type === 'email' && val !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
        return 'Please provide a valid email address.';
    }
    if (field.type === 'number' && val !== '' && parseInt(val, 10) <= 0) {
        return label + ' must be a positive number.';
    }
    if (field.tagName === 'SELECT' && field.hasAttribute('required') && val === '') {
        return label + ' is required.';
    }
    // File size check.
    if (field.type === 'file' && field.files && field.files.length > 0) {
        var maxBytes = parseInt(field.getAttribute('data-max-bytes') || '0', 10);
        if (maxBytes > 0 && field.files[0].size > maxBytes) {
            var maxMB = (maxBytes / (1024 * 1024)).toFixed(0);
            return label + ' must be under ' + maxMB + ' MB.';
        }
        var allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (field.accept && field.files[0].type && allowed.indexOf(field.files[0].type) === -1) {
            return label + ' must be a JPEG, PNG, or GIF image.';
        }
    }
    return null;
}

/**
 * Attach enhanced validation to all forms with class `validated-form`.
 */
function initValidatedForms() {
    document.querySelectorAll('form.validated-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            clearInlineErrors(form);
            var errorMessages = [];

            form.querySelectorAll('input, select, textarea').forEach(function (field) {
                if (field.type === 'hidden' || field.disabled) return;
                var err = validateField(field);
                if (err) {
                    errorMessages.push(err);
                    addInlineError(field, err);
                    // Shake animation.
                    field.classList.add('shake');
                    field.addEventListener('animationend', function () {
                        field.classList.remove('shake');
                    }, { once: true });
                }
            });

            if (errorMessages.length > 0) {
                e.preventDefault();
                showToast(errorMessages, 'error');
                // Scroll to first error.
                var first = form.querySelector('.input-error-js');
                if (first) { first.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
            } else {
                // Disable submit button to prevent double-submit.
                var btn = form.querySelector('[type="submit"]');
                if (btn && !btn.disabled) {
                    btn.disabled = true;
                    btn.textContent = 'Submitting…';
                }
            }
        });

        // Clear field error on change/input.
        form.querySelectorAll('input, select, textarea').forEach(function (field) {
            ['input', 'change'].forEach(function (evt) {
                field.addEventListener(evt, function () {
                    if (field.classList.contains('input-error-js')) {
                        field.classList.remove('input-error-js');
                        var parent = field.closest('label') || field.parentNode;
                        parent.querySelectorAll('.js-field-error').forEach(function (el) { el.remove(); });
                    }
                });
            });
        });
    });
}

/**
 * Legacy: basic validation for non-validated-form forms.
 */
function initAuthFormValidation() {
    document.querySelectorAll('.auth-form:not(.validated-form)').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var invalid = false;
            form.querySelectorAll('input[required]').forEach(function (input) {
                if (!input.value.trim()) {
                    invalid = true;
                    input.classList.add('input-error');
                } else {
                    input.classList.remove('input-error');
                }
            });
            if (invalid) { event.preventDefault(); }
        });
    });
}

/**
 * Boot all initializers.
 */
function boot() {
    initValidatedForms();
    initAuthFormValidation();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
