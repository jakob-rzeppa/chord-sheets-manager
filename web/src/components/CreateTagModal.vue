<script setup lang="ts">
import { createTag } from '@/services/api/tagClient';
import { ref } from 'vue';

const emit = defineEmits(['close']);

const name = ref('');

async function handleSubmit() {
    await createTag({ name: name.value });
    emit('close');
}
</script>

<template>
    <div class="space-y-6">
        <h2 class="text-2xl font-bold text-center">Create New Tag</h2>
        <div @keypress.enter.prevent="handleSubmit" class="space-y-4">
            <div>
                <label for="tag-name" class="label">
                    <span class="label-text text-base font-semibold">Tag Name</span>
                </label>
                <input
                    v-model="name"
                    type="text"
                    id="tag-name"
                    class="input input-bordered w-full"
                    placeholder="Enter tag name..."
                    required
                />
            </div>
            <div class="flex justify-end gap-2">
                <button class="btn btn-primary" @click="handleSubmit">Create Tag</button>
            </div>
        </div>
    </div>
</template>
