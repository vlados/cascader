/**
 * Cascader Alpine.js Component
 *
 * A cascading dropdown component for hierarchical data selection.
 * Supports desktop (two-column) and mobile (bottom sheet) views.
 */

export function cascader({ options, selectedValue, initialText, valueField = 'id', labelField = 'name' }) {
    return {
        options: options || [],
        selectedValue: selectedValue,
        selectedText: initialText,
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

        getValue(item) {
            return item?.[this.valueField];
        },

        getLabel(item) {
            return item?.label || item?.[this.labelField];
        },

        get isSearching() {
            return this.search.trim().length > 0;
        },

        get searchResults() {
            if (!this.isSearching) return [];

            const query = this.search.toLowerCase().trim();
            const results = [];

            for (const parent of this.options) {
                if (this.getLabel(parent).toLowerCase().includes(query)) {
                    results.push({
                        ...parent,
                        _isParent: true,
                        _parentLabel: null
                    });
                }

                if (parent.children) {
                    for (const child of parent.children) {
                        if (this.getLabel(child).toLowerCase().includes(query)) {
                            results.push({
                                ...child,
                                _isParent: false,
                                _parentLabel: this.getLabel(parent),
                                _parent: parent
                            });
                        }
                    }
                }
            }

            return results;
        },

        get currentChildren() {
            const parent = this.hoveredParent || this.findParentByChildValue(this.selectedValue);
            return parent?.children || [];
        },

        get selectedParentValue() {
            const parent = this.findParentByChildValue(this.selectedValue);
            return parent ? this.getValue(parent) : null;
        },

        // Mobile: get children of selected parent
        get mobileChildren() {
            return this.mobileSelectedParent?.children || [];
        },

        // Mobile: get label of selected child
        get mobileSelectedChildLabel() {
            if (!this.mobileSelectedParent || !this.tempSelectedValue) return null;
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
                this.tempSelectedValue = this.selectedValue;
                this.tempSelectedText = this.selectedText;

                // If there's an existing selection, restore the state
                if (this.selectedValue) {
                    const parent = this.findParentByChildValue(this.selectedValue);
                    if (parent) {
                        // Check if selected value is the parent itself
                        if (this.getValue(parent) === this.selectedValue) {
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
                this.tempSelectedText = null;
            }
        },

        // Mobile: cancel and close
        mobileCancel() {
            this.closeCascader();
        },

        // Mobile: confirm selection
        mobileConfirm() {
            if (this.tempSelectedValue) {
                this.selectedValue = this.tempSelectedValue;
                this.selectedText = this.tempSelectedText;
            }
            this.closeCascader();
        },

        // Mobile: select a parent
        mobileSelectParent(parent) {
            const hasChildren = parent.children && parent.children.length > 0;

            if (!hasChildren) {
                // Parent is a leaf node, select it
                this.tempSelectedValue = this.getValue(parent);
                this.tempSelectedText = this.getLabel(parent);
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
            this.tempSelectedText = this.getLabel(this.mobileSelectedParent) + ' / ' + this.getLabel(child);
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
                this.selectedValue = this.getValue(parent);
                this.selectedText = this.getLabel(parent);
                this.hoveredParent = null;
                this.hoveredParentValue = null;
                this.search = '';
                this.open = false;
                this.$refs.desktopDialog?.close();
            }
        },

        selectChild(child) {
            const parent = this.hoveredParent || this.findParentByChildValue(this.getValue(child));
            if (parent) {
                this.selectedText = this.getLabel(parent) + ' / ' + this.getLabel(child);
            } else {
                this.selectedText = this.getLabel(child);
            }
            this.selectedValue = this.getValue(child);
            this.hoveredParent = null;
            this.hoveredParentValue = null;
            this.search = '';
            this.open = false;
            this.$refs.desktopDialog?.close();
        },

        selectSearchResult(result) {
            if (result._isParent) {
                this.selectedValue = this.getValue(result);
                this.selectedText = this.getLabel(result);
            } else {
                this.selectedValue = this.getValue(result);
                this.selectedText = result._parentLabel + ' / ' + this.getLabel(result);
            }
            this.hoveredParent = null;
            this.hoveredParentValue = null;
            this.search = '';
            this.open = false;
            this.$refs.desktopDialog?.close();
        },

        findParentByChildValue(value) {
            if (!value) return null;
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
            this.selectedValue = null;
            this.selectedText = null;
            this.hoveredParent = null;
            this.hoveredParentValue = null;
            this.search = '';
        }
    };
}

// Auto-register if Alpine is available globally
if (typeof window !== 'undefined' && window.Alpine) {
    window.Alpine.data('cascader', cascader);
}

export default cascader;
