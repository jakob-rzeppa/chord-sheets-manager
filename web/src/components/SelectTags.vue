<script setup lang="ts">
import { onMounted, computed } from 'vue';
import type { Tag } from '@/types/types.ts';
import ErrorDisplay from '@/components/ErrorDisplay.vue';
import LoadingPlaceholder from '@/components/LoadingPlaceholder.vue';
import { useTagStore } from '@/stores/tagStore';
import { fetchAllTags } from '@/services/api/tagClient';
import { useTagSelect } from '@/composables/useTagSelect';

const model = defineModel<Tag[]>({ required: true });

const tagStore = useTagStore();

// Filter out already selected tags from dropdown
const availableTags = computed(() => 
    tagStore.tags.filter((tag: Tag) => !model.value.find((t) => t.id === tag.id))
);

const {
    inputValue,
    showDropdown,
    highlightedIndex,
    filteredTags,
    addTag,
    removeTag,
    handleInput,
    handleKeydown,
    handleBlur,
    handleFocus,
} = useTagSelect(availableTags, model, 'tags-dropdown');

onMounted(() => {
    fetchAllTags();
});
</script>

<template>
    <LoadingPlaceholder v-if="tagStore.loading" />
    <ErrorDisplay v-else-if="tagStore.error" :message="tagStore.error" />
    <div v-else class="relative">
        <label class="label">
            <span class="label-text text-base font-semibold">Tags</span>
            <span class="label-text-alt">Press Enter to add</span>
        </label>

        <div class="join w-full">
            <label class="input box-border w-full join-item relative">
                <span v-if="model.length > 0" class="label">
                    <span
                        v-for="tag in model"
                        :key="tag.id"
                        class="badge badge-primary gap-2 cursor-pointer hover:badge-secondary transition-colors"
                        @click="() => removeTag(tag.id)"
                    >
                        {{ tag.name }}
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </span>
                </span>
                <input
                    type="text"
                    placeholder="Type here"
                    v-model="inputValue"
                    @input="handleInput"
                    @focus="handleFocus"
                    @blur="handleBlur"
                    @keydown="handleKeydown"
                />
            </label>
        </div>
        
        <ul
            id="tags-dropdown"
            v-if="showDropdown && filteredTags.length > 0"
            class="absolute z-200 w-full mt-1 bg-base-100 border border-base-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
        >
            <li
                v-for="(tag, index) in filteredTags"
                :key="tag.id"
                @click="addTag(tag)"
                @mouseenter="highlightedIndex = index"
                class="px-4 py-2 cursor-pointer select-none"
                :class="{
                    'bg-base-200': highlightedIndex === index
                }"
            >
                {{ tag.name }}
            </li>
        </ul>
    </div>
</template>
