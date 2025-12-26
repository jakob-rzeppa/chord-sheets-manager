<script setup lang="ts">
import { onMounted, computed } from 'vue';
import type { Artist } from '@/types/types';
import ErrorDisplay from '@/components/ErrorDisplay.vue';
import LoadingPlaceholder from '@/components/LoadingPlaceholder.vue';
import { useArtistStore } from '@/stores/artistStore';
import { fetchAllArtists } from '@/services/api/artistClient';
import { useSearchableSelect } from '@/composables/useSearchableSelect';

const model = defineModel<Artist | null>({ required: true });

const artistStore = useArtistStore();

const artists = computed(() => artistStore.artists);

const {
    inputValue,
    showDropdown,
    highlightedIndex,
    filteredItems: filteredArtists,
    selectItem: selectArtist,
    clearSelection,
    handleInput,
    handleKeydown,
    handleBlur,
    handleFocus,
    initializeInput,
} = useSearchableSelect(artists, model, 'artist-dropdown');

onMounted(() => {
    fetchAllArtists();
    if (model.value) {
        initializeInput(model.value.name);
    }
});
</script>

<template>
    <LoadingPlaceholder v-if="artistStore.loading" />
    <ErrorDisplay v-else-if="artistStore.error" :message="artistStore.error" />
    <div v-else class="relative">
        <label class="label">
            <span class="label-text text-base font-semibold">Artist</span>
            <span v-if="!model" class="label-text-alt">Select one artist</span>
            <span v-else class="flex items-center gap-1 text-primary font-semibold">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                    />
                </svg>
                Selected
            </span>
        </label>

        <div class="relative">
            <input
                type="text"
                class="input input-bordered w-full"
                placeholder="Search artists..."
                v-model="inputValue"
                @input="handleInput"
                @focus="handleFocus"
                @blur="handleBlur"
                @keydown="handleKeydown"
            />
            <button
                v-if="model"
                @click="clearSelection"
                class="text-warning-content bg-warning absolute right-3 top-1/2 -translate-y-1/2 rounded-full p-1 hover:cursor-pointer hover:shadow-lg hover:brightness-90 transition-colors z-10"
                type="button"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
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
            </button>
        </div>

        <ul
            id="artist-dropdown"
            v-if="showDropdown && filteredArtists.length > 0"
            class="absolute z-200 w-full mt-1 bg-base-100 border border-base-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
        >
            <li
                v-for="(artist, index) in filteredArtists"
                :key="artist.id"
                @click="selectArtist(artist)"
                @mouseenter="highlightedIndex = index"
                class="px-4 py-2 cursor-pointer select-none"
                :class="{
                    'bg-primary text-primary-content': model?.id === artist.id,
                    'bg-base-200': highlightedIndex === index && model?.id !== artist.id
                }"
            >
                {{ artist.name }}
            </li>
        </ul>
    </div>
</template>
