import { ref, computed, type Ref } from 'vue';
import type { Tag } from '@/types/types';

export function useTagSelect(
    availableTags: Ref<Tag[]>,
    selectedTags: Ref<Tag[]>,
    dropdownId: string
) {
    const inputValue = ref<string>('');
    const showDropdown = ref<boolean>(false);
    const highlightedIndex = ref<number>(-1);

    const filteredTags = computed(() => {
        if (!inputValue.value) {
            return availableTags.value;
        }

        const query = inputValue.value.toLowerCase();

        return availableTags.value
            .filter((tag: Tag) => tag.name.toLowerCase().includes(query))
            .sort((a: Tag, b: Tag) => {
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

    function addTag(tag: Tag) {
        // Avoid duplicates
        if (selectedTags.value.find((t) => t.id === tag.id)) {
            return;
        }

        selectedTags.value.push(tag);
        inputValue.value = '';
        showDropdown.value = false;
        highlightedIndex.value = -1;
    }

    // Add tag by exact name match from filtered tags
    // Only a fallback if user presses Enter without selecting from dropdown (normally not needed)
    function addTagByName() {
        if (!inputValue.value.trim()) {
            return;
        }

        // Try to find exact match by name from filtered tags
        const tagToAdd = filteredTags.value.find(
            (tag: Tag) => tag.name.toLowerCase() === inputValue.value.toLowerCase()
        );

        if (!tagToAdd) {
            return;
        }

        addTag(tagToAdd);
    }

    function removeTag(id?: number) {
        let indexToRemove;

        if (id === undefined) {
            indexToRemove = selectedTags.value.length - 1;
        } else {
            indexToRemove = selectedTags.value.findIndex((tag: Tag) => tag.id === id);
        }

        if (indexToRemove === -1) {
            return;
        }

        selectedTags.value.splice(indexToRemove, 1);
    }

    function handleInput() {
        showDropdown.value = true;
        highlightedIndex.value = 0;
    }

    function handleKeydown(event: KeyboardEvent) {
        // Handle backspace when input is empty - remove last tag
        if (event.key === 'Backspace' && inputValue.value === '') {
            removeTag();
            return;
        }

        // Handle Enter key
        if (event.key === 'Enter') {
            event.preventDefault();

            // If dropdown is open and there's a highlighted item, select it
            if (showDropdown.value && highlightedIndex.value >= 0 && filteredTags.value.length > 0) {
                addTag(filteredTags.value[highlightedIndex.value]);
            } else {
                // Otherwise try to add by exact match
                addTagByName();
            }
            return;
        }

        // If dropdown is not shown or empty, don't handle navigation keys
        if (!showDropdown.value || filteredTags.value.length === 0) {
            return;
        }

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                highlightedIndex.value = Math.min(
                    highlightedIndex.value + 1,
                    filteredTags.value.length - 1
                );
                scrollToHighlighted();
                break;
            case 'ArrowUp':
                event.preventDefault();
                highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
                scrollToHighlighted();
                break;
            case 'Tab':
                event.preventDefault();
                const nextIndex = highlightedIndex.value + 1;
                highlightedIndex.value = nextIndex >= filteredTags.value.length ? 0 : nextIndex;
                scrollToHighlighted();
                break;
            case 'Escape':
                showDropdown.value = false;
                highlightedIndex.value = -1;
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
        }, 200);
    }

    function handleFocus() {
        showDropdown.value = true;
        highlightedIndex.value = 0;
    }

    return {
        inputValue,
        showDropdown,
        highlightedIndex,
        filteredTags,
        addTag,
        addTagByName,
        removeTag,
        handleInput,
        handleKeydown,
        handleBlur,
        handleFocus,
    };
}
