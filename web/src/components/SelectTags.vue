<script setup lang="ts">
import { ref, onMounted } from 'vue';
import type { Tag } from '@/types/types.ts';
import ErrorDisplay from '@/components/ErrorDisplay.vue';
import LoadingPlaceholder from '@/components/LoadingPlaceholder.vue';
import { useTagStore } from '@/stores/tagStore';
import { fetchAllTags } from '@/services/api/tagClient';

const model = defineModel<Tag[]>({ required: true });

const tagStore = useTagStore();

const inputValue = ref<string>('');

onMounted(() => {
    fetchAllTags();
});

function addActiveTag() {
    const tagToAdd = tagStore.tags.find(
        (tag: Tag) => tag.name.toLowerCase() === inputValue.value.toLowerCase(),
    );

    // Since errors are handled by the tagStore, we can just return early if something is wrong
    if (!tagToAdd) {
        return;
    }

    if (model.value.includes(tagToAdd)) {
        return;
    }

    model.value.push(tagToAdd);

    inputValue.value = '';
}

function removeActiveTag(id?: number) {
    let indexToRemove;

    if (id === undefined) {
        indexToRemove = model.value.length - 1;
    } else {
        indexToRemove = model.value.findIndex((tag: Tag) => tag.id === id);
    }

    if (indexToRemove === -1) {
        return;
    }

    model.value.splice(indexToRemove, 1);
}
</script>

<template>
    <LoadingPlaceholder v-if="tagStore.loading" />
    <ErrorDisplay v-else-if="tagStore.error" :message="tagStore.error" />
    <div v-else>
        <label class="label">
            <span class="label-text text-base font-semibold">Tags</span>
            <span class="label-text-alt">Press Enter to add</span>
        </label>

        <div class="join w-full">
            <label class="input box-border w-full join-item">
                <span v-if="model.length > 0" class="label">
                    <span
                        v-for="tag in model"
                        :key="tag.id"
                        class="badge badge-primary gap-2 cursor-pointer hover:badge-secondary transition-colors"
                        @click="() => removeActiveTag(tag.id)"
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
                    list="tags"
                    placeholder="Type here"
                    v-model="inputValue"
                    @keypress.enter.stop="addActiveTag"
                    @keydown.delete.stop="
                        () => {
                            if (inputValue === '') removeActiveTag();
                        }
                    "
                />
            </label>
            <button class="btn btn-primary join-item" @click="addActiveTag">
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
                        d="M12 4v16m8-8H4"
                    />
                </svg>
            </button>
        </div>
        <datalist id="tags">
            <option
                v-for="tag in tagStore.tags.filter(
                    (el: Tag) => model.findIndex((m) => m.id === el.id) === -1,
                )"
                :key="tag.id"
                :value="tag.name"
            />
        </datalist>
    </div>
</template>
