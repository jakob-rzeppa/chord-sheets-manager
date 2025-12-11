<script setup lang="ts">
import { createArtist } from '@/services/api/artistClient';
import { ref } from 'vue';

const emit = defineEmits(['close']);

const name = ref('');

function handleSubmit() {
    createArtist({ name: name.value });
    emit('close');
}
</script>

<template>
    <div class="space-y-6">
        <h2 class="text-2xl font-bold text-center">Create New Artist</h2>
        <div @keypress.enter.prevent="handleSubmit" class="space-y-4">
            <div>
                <label for="artist-name" class="label">
                    <span class="label-text text-base font-semibold">Artist Name</span>
                </label>
                <input
                    v-model="name"
                    type="text"
                    id="artist-name"
                    class="input input-bordered w-full"
                    placeholder="Enter artist name..."
                    required
                />
            </div>
            <div class="flex justify-end gap-2">
                <button class="btn btn-primary" @click="handleSubmit">Create Artist</button>
            </div>
        </div>
    </div>
</template>
