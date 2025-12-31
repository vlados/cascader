/**
 * Cascader Alpine.js Component
 *
 * A cascading dropdown component for hierarchical data selection.
 * Supports unlimited depth levels, desktop (multi-column) and mobile (bottom sheet) views.
 * Includes optional multi-select mode with checkboxes.
 */

export function cascader({
    options,
    selectedValue,
    initialText,
    valueField = 'id',
    labelField = 'name',
    multiple = false
}) {
    return {
        options: options || [],
        selectedValue: selectedValue,
        selectedText: initialText,
        valueField: valueField,
        labelField: labelField,
        multiple: multiple,
        open: false,
        search: '',

        // Multi-level navigation state (array of selected options per level)
        // levels[0] = selected option at level 0, levels[1] = selected option at level 1, etc.
        levels: [],

        // Mobile-specific state
        isMobile: false,
        mobileLevel: 0,
        mobilePath: [], // Breadcrumb path of selected options
        tempSelectedValue: null,
        tempSelectedText: null,

        // Dropdown position for teleported element
        dropdownPosition: { top: 0, left: 0, width: 0 },

        init() {
            this.checkMobile();
            window.addEventListener('resize', () => {
                this.checkMobile();
                if (this.open && !this.isMobile) {
                    this.updateDropdownPosition();
                }
            });
            window.addEventListener('scroll', () => {
                if (this.open && !this.isMobile) {
                    this.updateDropdownPosition();
                }
            }, true);

            // Initialize selected text from value if not provided
            if (this.selectedValue && !this.selectedText) {
                this.updateSelectedTextFromValue();
            }
        },

        updateDropdownPosition() {
            const root = this.$refs.cascaderRoot;
            if (!root) return;

            const rect = root.getBoundingClientRect();
            this.dropdownPosition = {
                top: rect.bottom + 4,
                left: rect.left,
                width: rect.width
            };
        },

        checkMobile() {
            this.isMobile = window.innerWidth < 640;
        },

        getValue(item) {
            return item?.[this.valueField];
        },

        getLabel(item) {
            return item?.label || item?.[this.labelField];
        },

        hasChildren(item) {
            return item?.children && item.children.length > 0;
        },

        get isSearching() {
            return this.search.trim().length > 0;
        },

        /**
         * Recursively search through all levels of options.
         */
        get searchResults() {
            if (!this.isSearching) return [];

            const query = this.search.toLowerCase().trim();
            const results = [];

            const searchRecursive = (items, path = []) => {
                for (const item of items) {
                    const currentPath = [...path, item];
                    const label = this.getLabel(item).toLowerCase();

                    if (label.includes(query)) {
                        results.push({
                            ...item,
                            _path: currentPath,
                            _pathLabels: currentPath.map(p => this.getLabel(p)),
                            _isLeaf: !this.hasChildren(item)
                        });
                    }

                    if (this.hasChildren(item)) {
                        searchRecursive(item.children, currentPath);
                    }
                }
            };

            searchRecursive(this.options);
            return results;
        },

        /**
         * Get options for a specific column level.
         * Level 0 shows root options, level 1 shows children of selected level 0 option, etc.
         */
        getOptionsForLevel(level) {
            if (level === 0) {
                return this.options;
            }

            const parentOption = this.levels[level - 1];
            return parentOption?.children || [];
        },

        /**
         * Get the number of columns to display (based on current navigation depth).
         */
        get columnCount() {
            // Always show at least 1 column
            // Show additional columns based on navigation depth
            let count = 1;
            for (let i = 0; i < this.levels.length; i++) {
                if (this.levels[i] && this.hasChildren(this.levels[i])) {
                    count++;
                }
            }
            return count;
        },

        /**
         * Check if an option is currently selected/hovered at its level.
         */
        isOptionActive(option, level) {
            return this.levels[level] && this.getValue(this.levels[level]) === this.getValue(option);
        },

        /**
         * Check if an option is the final selected value (for checkmark display).
         */
        isOptionSelected(option) {
            if (this.multiple) {
                return this.isValueSelected(this.getValue(option));
            }
            return this.selectedValue === this.getValue(option);
        },

        /**
         * For multi-select: check if a value is in the selected array.
         */
        isValueSelected(value) {
            if (!this.multiple || !this.selectedValue) return false;
            return Array.isArray(this.selectedValue) && this.selectedValue.includes(value);
        },

        /**
         * Navigate into an option at a specific level.
         */
        navigateToOption(option, level) {
            // Update the levels array
            this.levels = this.levels.slice(0, level);
            this.levels[level] = option;

            // If this is a leaf node in single-select mode, select it
            if (!this.hasChildren(option) && !this.multiple) {
                this.selectOption(option, level);
            }
        },

        /**
         * Select an option (for single-select mode).
         */
        selectOption(option, level) {
            // Build the full path text
            const pathLabels = [];
            for (let i = 0; i <= level; i++) {
                if (this.levels[i]) {
                    pathLabels.push(this.getLabel(this.levels[i]));
                }
            }

            this.selectedValue = this.getValue(option);
            this.selectedText = pathLabels.join(' / ');
            this.closeCascader();
        },

        /**
         * Toggle checkbox selection for multi-select mode.
         */
        toggleCheckbox(option, level) {
            if (!this.multiple) return;

            const value = this.getValue(option);

            // Initialize as array if needed
            if (!Array.isArray(this.selectedValue)) {
                this.selectedValue = this.selectedValue ? [this.selectedValue] : [];
            }

            const index = this.selectedValue.indexOf(value);
            if (index > -1) {
                // Remove from selection
                this.selectedValue = this.selectedValue.filter(v => v !== value);
            } else {
                // Add to selection
                this.selectedValue = [...this.selectedValue, value];
            }

            this.updateMultiSelectText();
        },

        /**
         * Update display text for multi-select mode.
         */
        updateMultiSelectText() {
            if (!this.multiple || !this.selectedValue || !Array.isArray(this.selectedValue)) {
                this.selectedText = null;
                return;
            }

            if (this.selectedValue.length === 0) {
                this.selectedText = null;
                return;
            }

            // Find labels for all selected values
            const labels = [];
            const findLabel = (items, targetValue, path = []) => {
                for (const item of items) {
                    const currentPath = [...path, this.getLabel(item)];
                    if (this.getValue(item) === targetValue) {
                        return currentPath.join(' / ');
                    }
                    if (this.hasChildren(item)) {
                        const found = findLabel(item.children, targetValue, currentPath);
                        if (found) return found;
                    }
                }
                return null;
            };

            for (const value of this.selectedValue) {
                const label = findLabel(this.options, value);
                if (label) labels.push(label);
            }

            this.selectedText = labels.length > 0 ? `${labels.length} selected` : null;
        },

        /**
         * Update selected text from value (for initialization).
         */
        updateSelectedTextFromValue() {
            if (this.multiple) {
                this.updateMultiSelectText();
                return;
            }

            if (!this.selectedValue) {
                this.selectedText = null;
                return;
            }

            const findPath = (items, targetValue, path = []) => {
                for (const item of items) {
                    const currentPath = [...path, this.getLabel(item)];
                    if (this.getValue(item) === targetValue) {
                        return currentPath;
                    }
                    if (this.hasChildren(item)) {
                        const found = findPath(item.children, targetValue, currentPath);
                        if (found) return found;
                    }
                }
                return null;
            };

            const path = findPath(this.options, this.selectedValue);
            this.selectedText = path ? path.join(' / ') : null;
        },

        /**
         * Select a search result.
         */
        selectSearchResult(result) {
            if (this.multiple) {
                // Toggle checkbox for the result
                const value = this.getValue(result);
                if (!Array.isArray(this.selectedValue)) {
                    this.selectedValue = this.selectedValue ? [this.selectedValue] : [];
                }
                const index = this.selectedValue.indexOf(value);
                if (index > -1) {
                    this.selectedValue = this.selectedValue.filter(v => v !== value);
                } else {
                    this.selectedValue = [...this.selectedValue, value];
                }
                this.updateMultiSelectText();
            } else {
                this.selectedValue = this.getValue(result);
                this.selectedText = result._pathLabels.join(' / ');
                this.closeCascader();
            }
        },

        /**
         * Find the path to a value in the options tree.
         */
        findPathToValue(value, items = null, path = []) {
            items = items || this.options;
            for (const item of items) {
                const currentPath = [...path, item];
                if (this.getValue(item) === value) {
                    return currentPath;
                }
                if (this.hasChildren(item)) {
                    const found = this.findPathToValue(value, item.children, currentPath);
                    if (found) return found;
                }
            }
            return null;
        },

        openCascader() {
            this.open = true;
            this.levels = [];

            // Restore navigation state from selected value
            if (this.selectedValue && !this.multiple) {
                const path = this.findPathToValue(this.selectedValue);
                if (path) {
                    this.levels = path;
                }
            }

            if (!this.isMobile) {
                this.$nextTick(() => {
                    this.updateDropdownPosition();
                    this.$refs.desktopDialog?.showModal();
                    this.$refs.searchInput?.focus();
                });
            } else {
                // Initialize mobile state
                this.mobileLevel = 0;
                this.mobilePath = [];
                this.tempSelectedValue = this.multiple
                    ? (Array.isArray(this.selectedValue) ? [...this.selectedValue] : (this.selectedValue ? [this.selectedValue] : []))
                    : this.selectedValue;
                this.tempSelectedText = this.selectedText;

                // Restore mobile navigation state from selected value
                if (this.selectedValue && !this.multiple) {
                    const path = this.findPathToValue(this.selectedValue);
                    if (path && path.length > 0) {
                        this.mobilePath = path.slice(0, -1);
                        this.mobileLevel = this.mobilePath.length;
                    }
                }

                this.$nextTick(() => {
                    this.$refs.mobileDialog?.showModal();
                });
            }
        },

        closeCascader() {
            this.open = false;
            this.search = '';
            this.levels = [];

            this.$refs.desktopDialog?.close();
            this.$refs.mobileDialog?.close();

            if (this.isMobile) {
                this.mobileLevel = 0;
                this.mobilePath = [];
                this.tempSelectedValue = null;
                this.tempSelectedText = null;
            }
        },

        // ==================== Mobile Methods ====================

        mobileCancel() {
            this.closeCascader();
        },

        mobileConfirm() {
            if (this.multiple) {
                this.selectedValue = this.tempSelectedValue;
                this.updateMultiSelectText();
            } else if (this.tempSelectedValue) {
                this.selectedValue = this.tempSelectedValue;
                this.selectedText = this.tempSelectedText;
            }
            this.closeCascader();
        },

        /**
         * Get options for the current mobile level.
         */
        get mobileOptions() {
            if (this.mobileLevel === 0) {
                return this.options;
            }
            const parent = this.mobilePath[this.mobileLevel - 1];
            return parent?.children || [];
        },

        /**
         * Select an option on mobile.
         */
        mobileSelectOption(option) {
            if (this.hasChildren(option)) {
                // Navigate deeper
                this.mobilePath = this.mobilePath.slice(0, this.mobileLevel);
                this.mobilePath.push(option);
                this.mobileLevel++;
            } else if (this.multiple) {
                // Toggle checkbox
                const value = this.getValue(option);
                if (!Array.isArray(this.tempSelectedValue)) {
                    this.tempSelectedValue = this.tempSelectedValue ? [this.tempSelectedValue] : [];
                }
                const index = this.tempSelectedValue.indexOf(value);
                if (index > -1) {
                    this.tempSelectedValue = this.tempSelectedValue.filter(v => v !== value);
                } else {
                    this.tempSelectedValue = [...this.tempSelectedValue, value];
                }
            } else {
                // Select leaf option
                const pathLabels = [...this.mobilePath.map(p => this.getLabel(p)), this.getLabel(option)];
                this.tempSelectedValue = this.getValue(option);
                this.tempSelectedText = pathLabels.join(' / ');
            }
        },

        /**
         * Navigate to a specific level via breadcrumb.
         */
        mobileGoToLevel(level) {
            this.mobileLevel = level;
            this.mobilePath = this.mobilePath.slice(0, level);
        },

        /**
         * Check if an option is selected in mobile temp state.
         */
        isMobileTempSelected(option) {
            const value = this.getValue(option);
            if (this.multiple) {
                return Array.isArray(this.tempSelectedValue) && this.tempSelectedValue.includes(value);
            }
            return this.tempSelectedValue === value;
        },

        clearSearch() {
            this.search = '';
        },

        clear() {
            this.selectedValue = this.multiple ? [] : null;
            this.selectedText = null;
            this.levels = [];
            this.search = '';
        },

        /**
         * Get the count of selected items (for multi-select display).
         */
        get selectedCount() {
            if (!this.multiple || !this.selectedValue) return 0;
            return Array.isArray(this.selectedValue) ? this.selectedValue.length : 0;
        }
    };
}

// Auto-register if Alpine is available globally
if (typeof window !== 'undefined' && window.Alpine) {
    window.Alpine.data('cascader', cascader);
}

export default cascader;
