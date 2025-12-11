<script setup lang="ts">
import { useRouter } from 'vue-router';
import SelectArtist from '@/components/SelectArtist.vue';
import SelectTags from '@/components/SelectTags.vue';
import PlusIcon from '@/components/icons/PlusIcon.vue';
import { ref } from 'vue';
import { useSheetStore } from '@/stores/sheetStore';
import type { Sheet } from '@/types/types';
import { useModalStore } from '@/stores/modalStore';
import CreateArtistModal from '@/components/CreateArtistModal.vue';
import BaseEditView from '../BaseEditView.vue';
import { createSheet } from '@/services/api/sheetClient';
import CreateTagModal from '@/components/CreateTagModal.vue';

const router = useRouter();

const sheetStore = useSheetStore();
const modalStore = useModalStore();

const newSheet = ref<Omit<Sheet, 'id'>>({
    title: '',
    artist: null,
    tags: [],
    capo: 0,
    sourceURL: '',
    content: '',
});

const handleSave = async () => {
    if (!newSheet.value.title || !newSheet.value.content) {
        return;
    }

    await createSheet(newSheet.value);

    if (!sheetStore.error) {
        router.push({ name: 'sheetSearch' });
    }
};
</script>

<template>
    <BaseEditView
        elementType="Sheet"
        :back-to="{ name: 'sheetSearch' }"
        @save="handleSave"
        :is-new-element="true"
    >
        <template #content>
            <!-- Title -->
            <div>
                <label class="label" for="title">
                    <span class="label-text text-base font-semibold">Title</span>
                </label>
                <input
                    id="title"
                    v-model="newSheet.title"
                    type="text"
                    required
                    placeholder="Enter sheet title"
                    class="input input-bordered input-lg w-full"
                />
            </div>

            <!-- Artist -->
            <div class="space-y-2">
                <SelectArtist v-model="newSheet.artist" />

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
            <SelectTags v-model="newSheet.tags" />

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
                    v-model.number="newSheet.capo"
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
                    v-model="newSheet.sourceURL"
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
                    v-model="newSheet.content"
                    rows="20"
                    required
                    class="textarea textarea-bordered w-full font-mono text-sm h-96"
                    placeholder="Enter sheet notation here..."
                ></textarea>
            </div>
        </template>
    </BaseEditView>
</template>
