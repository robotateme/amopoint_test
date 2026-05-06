(function () {
    'use strict';

    function normalize(value) {
        return String(value || '').trim().toLowerCase();
    }

    function isTypeSelect(select) {
        var name = normalize(select.name);
        var id = normalize(select.id);

        if (name.indexOf('type') !== -1 || id.indexOf('type') !== -1 || name.indexOf('tip') !== -1 || id.indexOf('tip') !== -1) {
            return true;
        }

        var label = select.id ? document.querySelector('label[for="' + CSS.escape(select.id) + '"]') : null;

        return !!label && normalize(label.textContent).indexOf('тип') !== -1;
    }

    function fieldContainer(element) {
        return element.closest('tr, li, .form-group, .field, .control-group, p, label, div') || element;
    }

    function selectedToken(select) {
        var option = select.options[select.selectedIndex];
        var value = normalize(select.value);

        return value || normalize(option && option.text);
    }

    function update(typeSelect) {
        var token = selectedToken(typeSelect);
        var fields = document.querySelectorAll('input[name], textarea[name], select[name]');

        fields.forEach(function (field) {
            if (field === typeSelect) {
                fieldContainer(field).hidden = false;
                return;
            }

            var shouldShow = token !== '' && normalize(field.name).indexOf(token) !== -1;
            fieldContainer(field).hidden = !shouldShow;
        });
    }

    function init() {
        var typeSelect = Array.from(document.querySelectorAll('select')).find(isTypeSelect) || document.querySelector('select');

        if (!typeSelect) {
            return;
        }

        typeSelect.addEventListener('change', function () {
            update(typeSelect);
        });

        update(typeSelect);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
