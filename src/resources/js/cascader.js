/**
 * Cascader Alpine.js Component
 *
 * A cascading dropdown component for hierarchical data selection.
 * Supports desktop (two-column) and mobile (bottom sheet) views.
 */

export function cascader({ options, modelValue = null, selectedValue = null, initialText = null, valueField = 'id', labelField = 'name' }) {
    return {
        options: options || [],
        // `selectedValue` is the pre-0.5 name of this param, kept as an alias.
        modelValue: modelValue ?? selectedValue,
        selectedText: initialText ?? null,
        valueField: valueField,
        labelField: labelField,
        open: false,
        hoveredParent: null,
        hoveredParentValue: null,
        search: '',

        // Mobile-specific state
        isMobile: false,
        mobileLevel: 0, // 0 = parents, 1 = children
        mobileSelectedParent: null,
        tempSelectedValue: null,

        // Dropdown position for teleported element
        dropdownPosition: { top: 0, left: 0, width: 0 },

        init() {
            if (!this.selectedText) {
                this.selectedText = this.labelForValue(this.modelValue);
            }

            // Keeps the trigger label in sync with external changes too
            // (e.g. Livewire updating the property server-side).
            this.$watch('modelValue', (value) => {
                this.selectedText = this.labelForValue(value);
            });

            this.checkMobile();
            this._onWindowResize = () => {
                const wasMobile = this.isMobile;
                this.checkMobile();

                // Crossing the breakpoint while open would leave the wrong
                // dialog showing — close and let the user reopen.
                if (this.open && this.isMobile !== wasMobile) {
                    this.closeCascader();
                    return;
                }

                if (this.open && !this.isMobile) {
                    this.updateDropdownPosition();
                }
            };
            this._onWindowScroll = () => {
                if (this.open && !this.isMobile) {
                    this.updateDropdownPosition();
                }
            };
            window.addEventListener('resize', this._onWindowResize);
            window.addEventListener('scroll', this._onWindowScroll, true);
        },

        destroy() {
            window.removeEventListener('resize', this._onWindowResize);
            window.removeEventListener('scroll', this._onWindowScroll, true);
        },

        updateDropdownPosition() {
            const root = this.$refs.cascaderRoot;
            if (!root) return;

            const rect = root.getBoundingClientRect();
            this.dropdownPosition = {
                top: rect.bottom + 4, // 4px gap (mt-1)
                left: rect.left,
                width: rect.width
            };
        },

        checkMobile() {
            this.isMobile = window.innerWidth < 640; // sm breakpoint
        },

        // Native dialog close (e.g. Escape) bypasses closeCascader(),
        // so the dialogs' @close handlers reset state through here.
        onDesktopDialogClose() {
            this.open = false;
            this.search = '';
            this.hoveredParent = null;
            this.hoveredParentValue = null;
        },

        iconBackground(color) {
            const value = color || '#6B7280';

            // The old hex+alpha trick only works for 6-digit hex values;
            // named colors and rgb()/hsl() go through color-mix() instead.
            return /^#[0-9a-fA-F]{6}$/.test(value)
                ? value + '20'
                : `color-mix(in srgb, ${value} 12%, transparent)`;
        },

        getValue(item) {
            return item?.[this.valueField];
        },

        getLabel(item) {
            return item?.[this.labelField] ?? item?.label ?? null;
        },

        // "No selection" means null/undefined/'' — plain falsiness would
        // reject legitimate values like 0.
        isEmpty(value) {
            return value === null || value === undefined || value === '';
        },

        labelForValue(value) {
            if (this.isEmpty(value)) return null;

            for (const parent of this.options) {
                if (this.getValue(parent) === value) {
                    return this.getLabel(parent);
                }
                for (const child of parent.children || []) {
                    if (this.getValue(child) === value) {
                        return this.getLabel(parent) + ' / ' + this.getLabel(child);
                    }
                }
            }

            return null;
        },

        get isSearching() {
            return this.search.trim().length > 0;
        },

        matchesQuery(item, query) {
            const label = this.getLabel(item);
            return label !== null && String(label).toLowerCase().includes(query);
        },

        get searchResults() {
            if (!this.isSearching) return [];

            const query = this.search.toLowerCase().trim();
            const results = [];

            for (const parent of this.options) {
                const children = parent.children || [];

                // Only leaves are selectable, so parents with children are
                // represented by their child paths — matching a parent lists
                // all of its children, same as the normal view's rules.
                if (children.length === 0) {
                    if (this.matchesQuery(parent, query)) {
                        results.push({
                            ...parent,
                            _isParent: true,
                            _parentLabel: null
                        });
                    }
                    continue;
                }

                const parentMatches = this.matchesQuery(parent, query);

                for (const child of children) {
                    if (parentMatches || this.matchesQuery(child, query)) {
                        results.push({
                            ...child,
                            _isParent: false,
                            _parentLabel: this.getLabel(parent)
                        });
                    }
                }
            }

            return results;
        },

        get currentChildren() {
            const parent = this.hoveredParent || this.findParentByChildValue(this.modelValue);
            return parent?.children || [];
        },

        get selectedParentValue() {
            const parent = this.findParentByChildValue(this.modelValue);
            return parent ? this.getValue(parent) : null;
        },

        // Mobile: get children of selected parent
        get mobileChildren() {
            return this.mobileSelectedParent?.children || [];
        },

        get hasTempSelection() {
            return !this.isEmpty(this.tempSelectedValue);
        },

        // Mobile: get label of selected child
        get mobileSelectedChildLabel() {
            if (!this.mobileSelectedParent || !this.hasTempSelection) return null;
            const child = this.mobileSelectedParent.children?.find(c => this.getValue(c) === this.tempSelectedValue);
            return child ? this.getLabel(child) : null;
        },

        openCascader() {
            this.open = true;

            if (!this.isMobile) {
                // Update dropdown position and show dialog
                this.$nextTick(() => {
                    this.updateDropdownPosition();
                    this.$refs.desktopDialog?.showModal();
                    // Focus search input
                    this.$refs.searchInput?.focus();
                });
            } else {
                // Initialize mobile state
                this.mobileLevel = 0;
                this.mobileSelectedParent = null;
                this.tempSelectedValue = this.modelValue;

                // If there's an existing selection, restore the state
                if (!this.isEmpty(this.modelValue)) {
                    const parent = this.findParentByChildValue(this.modelValue);
                    if (parent) {
                        // Check if selected value is the parent itself
                        if (this.getValue(parent) === this.modelValue) {
                            // Parent is selected (no children case)
                            this.mobileSelectedParent = null;
                            this.mobileLevel = 0;
                        } else {
                            // Child is selected
                            this.mobileSelectedParent = parent;
                            this.mobileLevel = 1;
                        }
                    }
                }

                // Show mobile dialog
                this.$nextTick(() => {
                    this.$refs.mobileDialog?.showModal();
                });
            }
        },

        closeCascader() {
            this.open = false;
            this.search = '';
            this.hoveredParent = null;
            this.hoveredParentValue = null;

            // Close dialogs
            this.$refs.desktopDialog?.close();
            this.$refs.mobileDialog?.close();

            if (this.isMobile) {
                this.mobileLevel = 0;
                this.mobileSelectedParent = null;
                this.tempSelectedValue = null;
            }
        },

        // Mobile: cancel and close
        mobileCancel() {
            this.closeCascader();
        },

        // Mobile: confirm selection
        mobileConfirm() {
            if (this.hasTempSelection) {
                this.modelValue = this.tempSelectedValue;
            }
            this.closeCascader();
        },

        // Mobile: select a parent
        mobileSelectParent(parent) {
            const hasChildren = parent.children && parent.children.length > 0;

            if (!hasChildren) {
                // Parent is a leaf node, select it
                this.tempSelectedValue = this.getValue(parent);
                this.mobileSelectedParent = null;
            } else {
                // Parent has children, go to level 1
                this.mobileSelectedParent = parent;
                this.mobileLevel = 1;
            }
        },

        // Mobile: select a child
        mobileSelectChild(child) {
            this.tempSelectedValue = this.getValue(child);
        },

        // Mobile: go back to parents
        mobileGoToParents() {
            this.mobileLevel = 0;
            this.mobileSelectedParent = null;
        },

        hoverParent(parent) {
            this.hoveredParent = parent;
            this.hoveredParentValue = this.getValue(parent);
        },

        selectParent(parent) {
            const hasChildren = parent.children && parent.children.length > 0;

            if (!hasChildren) {
                this.modelValue = this.getValue(parent);
                this.hoveredParent = null;
                this.hoveredParentValue = null;
                this.search = '';
                this.open = false;
                this.$refs.desktopDialog?.close();
            }
        },

        selectChild(child) {
            this.modelValue = this.getValue(child);
            this.hoveredParent = null;
            this.hoveredParentValue = null;
            this.search = '';
            this.open = false;
            this.$refs.desktopDialog?.close();
        },

        selectSearchResult(result) {
            this.modelValue = this.getValue(result);
            this.hoveredParent = null;
            this.hoveredParentValue = null;
            this.search = '';
            this.open = false;
            this.$refs.desktopDialog?.close();
        },

        findParentByChildValue(value) {
            if (this.isEmpty(value)) return null;
            for (const parent of this.options) {
                if (this.getValue(parent) === value) return parent;
                if (parent.children) {
                    const child = parent.children.find(c => this.getValue(c) === value);
                    if (child) return parent;
                }
            }
            return null;
        },

        clearSearch() {
            this.search = '';
        },

        clear() {
            this.modelValue = null;
            this.hoveredParent = null;
            this.hoveredParentValue = null;
            this.search = '';
        }
    };
}

// Register with Alpine whether it is already started or still loading.
if (typeof window !== 'undefined') {
    if (window.Alpine) {
        window.Alpine.data('cascader', cascader);
    } else {
        document.addEventListener('alpine:init', () => {
            window.Alpine.data('cascader', cascader);
        });
    }
}

export default cascader;
