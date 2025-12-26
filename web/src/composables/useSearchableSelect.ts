import { ref, computed, type Ref } from 'vue';

interface Item {
    id?: number;
    name: string;
}

export function useSearchableSelect<T extends Item>(
    items: Ref<T[]>,
    selectedItem: Ref<T | null>,
    dropdownId: string
) {
    const inputValue = ref<string>('');
    const showDropdown = ref<boolean>(false);
    const highlightedIndex = ref<number>(-1);

    const filteredItems = computed(() => {
        if (!inputValue.value) {
            return items.value;
        }

        const query = inputValue.value.toLowerCase();

        return items.value
            .filter((item: T) => item.name.toLowerCase().includes(query))
            .sort((a: T, b: T) => {
                const aName = a.name.toLowerCase();
                const bName = b.name.toLowerCase();

                // Exact match first
                if (aName === query) return -1;
                if (bName === query) return 1;

                // Starts with query second
                const aStarts = aName.startsWith(query);
                const bStarts = bName.startsWith(query);
                if (aStarts && !bStarts) return -1;
                if (!aStarts && bStarts) return 1;

                // Earlier position in string third
                const aIndex = aName.indexOf(query);
                const bIndex = bName.indexOf(query);
                if (aIndex !== bIndex) return aIndex - bIndex;

                // Shorter names fourth (more specific)
                if (aName.length !== bName.length) return aName.length - bName.length;

                // Alphabetical last
                return aName.localeCompare(bName);
            });
    });

    function selectItem(item: T) {
        // Deep copy to avoid mutating store state
        selectedItem.value = JSON.parse(JSON.stringify(item));
        inputValue.value = item.name;
        showDropdown.value = false;
    }

    function clearSelection() {
        selectedItem.value = null;
        inputValue.value = '';
        showDropdown.value = false;
    }

    function handleInput() {
        showDropdown.value = true;
        highlightedIndex.value = 0;
        // Clear model if input doesn't match selected item
        if (selectedItem.value && inputValue.value !== selectedItem.value.name) {
            selectedItem.value = null;
        }
    }

    function handleKeydown(event: KeyboardEvent) {
        if (!showDropdown.value || filteredItems.value.length === 0) {
            return;
        }

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                highlightedIndex.value = Math.min(
                    highlightedIndex.value + 1,
                    filteredItems.value.length - 1
                );
                scrollToHighlighted();
                break;
            case 'ArrowUp':
                event.preventDefault();
                highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
                scrollToHighlighted();
                break;
            case 'Tab':
                if (highlightedIndex.value >= 0) {
                    event.preventDefault();
                    selectItem(filteredItems.value[highlightedIndex.value]);
                }
                break;
            case 'Enter':
                if (highlightedIndex.value >= 0) {
                    event.preventDefault();
                    selectItem(filteredItems.value[highlightedIndex.value]);
                }
                break;
            case 'Escape':
                showDropdown.value = false;
                break;
        }
    }

    function scrollToHighlighted() {
        const dropdown = document.getElementById(dropdownId);
        const highlighted = dropdown?.children[highlightedIndex.value] as HTMLElement;
        if (highlighted && dropdown) {
            const dropdownRect = dropdown.getBoundingClientRect();
            const highlightedRect = highlighted.getBoundingClientRect();

            if (highlightedRect.bottom > dropdownRect.bottom) {
                highlighted.scrollIntoView({ block: 'end', behavior: 'smooth' });
            } else if (highlightedRect.top < dropdownRect.top) {
                highlighted.scrollIntoView({ block: 'start', behavior: 'smooth' });
            }
        }
    }

    function handleBlur(event: FocusEvent) {
        // Check if focus moved to dropdown
        const relatedTarget = event.relatedTarget as HTMLElement;
        if (relatedTarget?.closest(`#${dropdownId}`)) {
            return;
        }

        // Delay to allow click on dropdown item
        setTimeout(() => {
            showDropdown.value = false;
            highlightedIndex.value = -1;
            // Reset to selected item name if exists
            if (selectedItem.value) {
                inputValue.value = selectedItem.value.name;
            }
        }, 200);
    }

    function handleFocus() {
        showDropdown.value = true;
        highlightedIndex.value = 0;
    }

    function initializeInput(initialValue?: string) {
        if (initialValue) {
            inputValue.value = initialValue;
        }
    }

    return {
        inputValue,
        showDropdown,
        highlightedIndex,
        filteredItems,
        selectItem,
        clearSelection,
        handleInput,
        handleKeydown,
        handleBlur,
        handleFocus,
        initializeInput,
    };
}
