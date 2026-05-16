/**
 * Autocomplete select (основна логіка)
 */

function isMobile() {
    return window.innerWidth <= 768;
}

function buildAutocomplete(select) {
    // На мобільних пристроях не створюємо кастомний компонент
    if (isMobile()) {
        select.style.display = '';
        select.classList.add('form-input', 'form-select');
        return;
    }
    
    if (select.dataset.acDone) return;
    select.dataset.acDone = '1';
    select.style.display = 'none';

    var placeholder = select.dataset.placeholder || '— Оберіть —';
    var submitOnChange = select.hasAttribute('data-submit-on-change');

    var options = [];
    for (var i = 0; i < select.options.length; i++) {
        options.push({
            value: select.options[i].value,
            text: select.options[i].textContent.trim(),
            selected: select.options[i].selected
        });
    }

    var currentOpt = options.find(function(o) { return o.selected && o.value; });
    var displayText = currentOpt ? currentOpt.text : placeholder;

    var wrap = document.createElement('div');
    wrap.className = 'ac-wrap';

    var display = document.createElement('div');
    display.className = 'ac-display' + (currentOpt ? ' has-value' : '');
    display.innerHTML = '<span class="ac-text">' + escapeHtml(displayText) + '</span>' +
        '<svg class="ac-arrow" width="12" height="12" viewBox="0 0 12 12"><path fill="currentColor" d="M6 8L1 3h10z"/></svg>';

    var dropdown = document.createElement('div');
    dropdown.className = 'ac-dropdown';

    var search = document.createElement('input');
    search.type = 'text';
    search.className = 'ac-input';
    search.placeholder = 'Пошук...';
    search.autocomplete = 'off';

    var list = document.createElement('div');
    list.className = 'ac-list';

    options.forEach(function(opt) {
        var item = document.createElement('div');
        item.className = 'ac-item' + (opt.selected && opt.value ? ' selected' : '');
        item.dataset.value = opt.value;
        item.textContent = opt.text;
        list.appendChild(item);
    });

    dropdown.appendChild(search);
    dropdown.appendChild(list);
    wrap.appendChild(display);
    wrap.appendChild(dropdown);
    select.parentNode.insertBefore(wrap, select.nextSibling);

    var isOpen = false;

    function open() {
        if (isOpen) return;
        closeAllAutocompletes();
        isOpen = true;
        wrap.classList.add('open');
        search.value = '';
        filterList('');
        search.focus();
        positionDropdown();
    }

    function close() {
        if (!isOpen) return;
        isOpen = false;
        wrap.classList.remove('open');
        wrap.classList.remove('drop-up');
        dropdown.classList.remove('drop-up');
    }

    function choose(value, text) {
        select.value = value;
        display.querySelector('.ac-text').textContent = text;
        display.classList.toggle('has-value', !!value);

        list.querySelectorAll('.ac-item').forEach(function(it) {
            it.classList.toggle('selected', it.dataset.value === value);
        });

        close();

        var evt = document.createEvent('HTMLEvents');
        evt.initEvent('change', true, false);
        select.dispatchEvent(evt);

        if (submitOnChange) {
            var form = select.closest('form');
            if (form) form.submit();
        }
    }

    function filterList(query) {
        var q = query.toLowerCase();
        var items = list.querySelectorAll('.ac-item');
        items.forEach(function(item) {
            var match = !q || item.textContent.toLowerCase().indexOf(q) !== -1;
            item.style.display = match ? '' : 'none';
        });
    }

    function positionDropdown() {
        var rect = wrap.getBoundingClientRect();
        var spaceBelow = window.innerHeight - rect.bottom;
        if (spaceBelow < 250) {
            wrap.classList.add('drop-up');
            dropdown.classList.add('drop-up');
        } else {
            wrap.classList.remove('drop-up');
            dropdown.classList.remove('drop-up');
        }
    }

    display.addEventListener('click', function(e) {
        e.stopPropagation();
        if (isOpen) close(); else open();
    });

    search.addEventListener('input', function() { filterList(this.value); });
    search.addEventListener('click', function(e) { e.stopPropagation(); });

    search.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') close();
        if (e.key === 'Enter') {
            e.preventDefault();
            var visible = list.querySelector('.ac-item:not([style*="display: none"])');
            if (visible) choose(visible.dataset.value, visible.textContent);
        }
    });

    list.addEventListener('click', function(e) {
        e.stopPropagation();
        var item = e.target.closest('.ac-item');
        if (item) choose(item.dataset.value, item.textContent);
    });

    document.addEventListener('click', function(e) {
        if (isOpen && !wrap.contains(e.target)) close();
    });
}