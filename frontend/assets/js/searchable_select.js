/**
 * SearchableSelect - Replaces standard HTML <select> with a searchable autocomplete input.
 */
class SearchableSelect {
    constructor(selectElement, options = {}) {
        if (!selectElement) return;
        this.select = typeof selectElement === 'string' ? document.querySelector(selectElement) : selectElement;
        if (!this.select || this.select.dataset.searchableInitialized) return;

        this.placeholder = options.placeholder || this.select.options[0]?.text || 'Search...';
        this.noResultsText = options.noResultsText || 'No matching items found';
        this.highlightIndex = -1;
        this.items = [];

        this.init();
    }

    static removeAccents(str) {
        if (!str) return '';
        return str.normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd').replace(/Đ/g, 'D')
            .toLowerCase();
    }

    init() {
        this.select.dataset.searchableInitialized = 'true';
        this.select.style.display = 'none';

        // Create container wrapper
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'searchable-select-wrapper';

        // Create input element
        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.className = 'searchable-select-input';
        this.input.autocomplete = 'off';
        this.input.placeholder = this.placeholder;

        // Copy required / disabled attributes if set
        if (this.select.required) this.input.required = true;
        if (this.select.disabled) this.input.disabled = true;

        // Arrow icon
        this.arrow = document.createElement('div');
        this.arrow.className = 'searchable-select-arrow';
        this.arrow.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>`;

        // Dropdown container
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'searchable-select-dropdown';

        // Assemble DOM
        this.wrapper.appendChild(this.input);
        this.wrapper.appendChild(this.arrow);
        this.wrapper.appendChild(this.dropdown);
        this.select.parentNode.insertBefore(this.wrapper, this.select.nextSibling);

        // Build options list
        this.parseOptions();
        this.updateInputValueFromSelect();

        // Bind events
        this.bindEvents();
    }

    parseOptions() {
        this.items = [];
        Array.from(this.select.options).forEach((opt, idx) => {
            this.items.push({
                value: opt.value,
                text: opt.text,
                normalizedText: SearchableSelect.removeAccents(opt.text),
                element: opt,
                index: idx
            });
        });
    }

    updateInputValueFromSelect() {
        const selectedOpt = this.select.options[this.select.selectedIndex];
        if (selectedOpt && selectedOpt.value !== '') {
            this.input.value = selectedOpt.text;
        } else {
            this.input.value = '';
        }
    }

    renderDropdown(filterText = '') {
        this.dropdown.innerHTML = '';
        const normalizedFilter = SearchableSelect.removeAccents(filterText.trim());

        const filtered = this.items.filter(item => {
            if (item.value === '' && normalizedFilter.length > 0) return false;
            if (!normalizedFilter) return true;
            return item.normalizedText.includes(normalizedFilter);
        });

        if (filtered.length === 0) {
            const noRes = document.createElement('div');
            noRes.className = 'searchable-select-no-results';
            noRes.textContent = this.noResultsText;
            this.dropdown.appendChild(noRes);
            this.highlightIndex = -1;
            return;
        }

        filtered.forEach((item, fIdx) => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'searchable-select-item';
            if (item.value === this.select.value) {
                itemDiv.classList.add('is-selected');
            }
            itemDiv.textContent = item.text;
            itemDiv.dataset.value = item.value;

            itemDiv.addEventListener('click', (e) => {
                e.stopPropagation();
                this.selectItem(item);
            });

            this.dropdown.appendChild(itemDiv);
        });

        this.highlightIndex = -1;
    }

    selectItem(item) {
        this.select.value = item.value;
        this.updateInputValueFromSelect();
        this.close();

        // Trigger native change event so external listeners fire
        const event = new Event('change', { bubbles: true });
        this.select.dispatchEvent(event);
    }

    open() {
        if (this.select.disabled) return;
        this.wrapper.classList.add('is-open');
        this.renderDropdown(this.input.value);
        
        // Auto select text on focus to allow easy replace
        this.input.select();
    }

    close() {
        this.wrapper.classList.remove('is-open');
        this.updateInputValueFromSelect();
    }

    bindEvents() {
        // Input focus & click opens dropdown
        this.input.addEventListener('focus', () => this.open());
        this.input.addEventListener('click', () => {
            if (!this.wrapper.classList.contains('is-open')) {
                this.open();
            }
        });

        // Typing filters options
        this.input.addEventListener('input', (e) => {
            if (!this.wrapper.classList.contains('is-open')) {
                this.wrapper.classList.add('is-open');
            }
            this.renderDropdown(e.target.value);
        });

        // Keyboard navigation
        this.input.addEventListener('keydown', (e) => {
            const dropdownItems = this.dropdown.querySelectorAll('.searchable-select-item');
            if (!dropdownItems.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.highlightIndex = Math.min(this.highlightIndex + 1, dropdownItems.length - 1);
                this.updateHighlight(dropdownItems);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.highlightIndex = Math.max(this.highlightIndex - 1, 0);
                this.updateHighlight(dropdownItems);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (this.highlightIndex >= 0 && dropdownItems[this.highlightIndex]) {
                    dropdownItems[this.highlightIndex].click();
                } else if (dropdownItems.length === 1) {
                    dropdownItems[0].click();
                }
            } else if (e.key === 'Escape') {
                this.close();
            }
        });

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!this.wrapper.contains(e.target) && e.target !== this.select) {
                this.close();
            }
        });

        // Listen for programmatically changed <select> options or value
        this.select.addEventListener('change', () => {
            this.updateInputValueFromSelect();
        });
    }

    updateHighlight(items) {
        items.forEach((el, idx) => {
            if (idx === this.highlightIndex) {
                el.classList.add('is-highlighted');
                el.scrollIntoView({ block: 'nearest' });
            } else {
                el.classList.remove('is-highlighted');
            }
        });
    }

    // Call this if select options were dynamically changed via JavaScript
    refresh() {
        this.parseOptions();
        this.updateInputValueFromSelect();
        if (this.wrapper.classList.contains('is-open')) {
            this.renderDropdown(this.input.value);
        }
    }
}
