<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';
import LoadingPlaceholder from '@/components/LoadingPlaceholder.vue';
import ErrorDisplay from '@/components/ErrorDisplay.vue';
import SelectArtist from '@/components/SelectArtist.vue';
import SelectTags from '@/components/SelectTags.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { useSheetStore } from '@/stores/sheetStore';
import type { Sheet } from '@/types/types';
import { useModalStore } from '@/stores/modalStore';
import CreateArtistModal from '@/components/CreateArtistModal.vue';
import PlusIcon from '@/components/icons/PlusIcon.vue';
import BaseEditView from '../BaseEditView.vue';
import { fetchSheet, updateSheet } from '@/services/api/sheetClient';
import CreateTagModal from '@/components/CreateTagModal.vue';

const route = useRoute();
const router = useRouter();

const sheetStore = useSheetStore();
const modalStore = useModalStore();

const sheetId = computed(() => route.params.id as string);

const localSheet = ref<Sheet | null>(null);

watch(
    () => sheetStore.detailedSheets[sheetId.value],
    (newSheet) => {
        if (newSheet) {
            // If we directly assign newSheet to localSheet, changes to localSheet would affect the store's state.
            localSheet.value = JSON.parse(JSON.stringify(newSheet));
        }
    },
    { immediate: true, deep: true },
);

onMounted(() => {
    fetchSheet(sheetId.value);
});

const handleSave = async () => {
    if (!localSheet.value) return;

    // Exclude the id field from the update payload
    const fieldsToUpdate = Object.assign({}, localSheet.value) as Partial<Sheet>;
    delete fieldsToUpdate.id;

    await updateSheet(sheetId.value, fieldsToUpdate);

    if (!sheetStore.error) {
        router.push({ name: 'sheet', params: { id: sheetId.value } });
    }
};
</script>

<template>
    <LoadingPlaceholder v-if="sheetStore.loading" />
    <ErrorDisplay v-else-if="sheetStore.error !== null" :message="sheetStore.error" />
    <ErrorDisplay
        v-else-if="localSheet === null"
        message="Something went wrong while loading the sheet."
    />
    <BaseEditView
        v-else
        :back-to="{ name: 'sheet', params: { id: sheetId } }"
        element-type="Sheet"
        @save="handleSave"
    >
        <template #content>
            <div @keypress.enter="handleSave" class="space-y-6">
                <!-- Title -->
                <div>
                    <label class="label" for="title">
                        <span class="label-text text-base font-semibold">Title</span>
                    </label>
                    <input
                        id="title"
                        v-model="localSheet.title"
                        type="text"
                        required
                        placeholder="Enter sheet title"
                        class="input input-bordered input-lg w-full"
                    />
                </div>

                <!-- Artist -->
                <div class="space-y-2">
                    <SelectArtist v-model="localSheet.artist" />

                    <button
                        class="btn btn-sm btn-outline btn-secondary w-max"
                        @click="modalStore.openModal(CreateArtistModal)"
                    >
                        <PlusIcon />
                        Create new Artist
                    </button>
                </div>

                <!-- Tags -->
                <div>
                    <SelectTags v-model="localSheet.tags" />

                    <button
                        class="btn btn-sm btn-outline btn-secondary w-max mt-2"
                        @click="modalStore.openModal(CreateTagModal)"
                    >
                        <PlusIcon />
                        Create new Tag
                    </button>
                </div>

                <!-- Capo -->
                <div>
                    <label class="label" for="capo">
                        <span class="label-text text-base font-semibold">Capo</span>
                        <span class="label-text-alt">Fret position (0-12)</span>
                    </label>
                    <input
                        id="capo"
                        v-model.number="localSheet.capo"
                        type="number"
                        min="0"
                        max="12"
                        class="input input-bordered input-lg w-full"
                    />
                </div>

                <!-- Source URL -->
                <div>
                    <label class="label" for="sourceURL">
                        <span class="label-text text-base font-semibold">Source URL</span>
                    </label>
                    <input
                        id="sourceURL"
                        v-model="localSheet.sourceURL"
                        type="text"
                        required
                        placeholder="Enter source URL"
                        class="input input-bordered input-lg w-full"
                    />
                </div>

                <!-- Content (Sheet notation) -->
                <div>
                    <label class="label" for="content">
                        <span class="label-text text-base font-semibold">Sheet Content</span>
                        <span class="label-text-alt">Sheet notation goes here</span>
                    </label>
                    <textarea
                        @keypress.enter.stop=""
                        id="content"
                        v-model="localSheet.content"
                        rows="20"
                        required
                        class="textarea textarea-bordered w-full font-mono text-sm h-96"
                        placeholder="Enter sheet notation here..."
                    ></textarea>
                </div>
            </div>
        </template>
    </BaseEditView>
</template>
