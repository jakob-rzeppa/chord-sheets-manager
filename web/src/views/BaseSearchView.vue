<script setup lang="ts">
import { ref } from 'vue';
import type { RouterLinkProps } from 'vue-router';
import ContentWrapper from '../components/ContentWrapper.vue';
import SearchIcon from '../components/icons/SearchIcon.vue';
import PlusIcon from '../components/icons/PlusIcon.vue';
import FilterIcon from '../components/icons/FilterIcon.vue';

const props = defineProps<{
    createRoute?: RouterLinkProps['to'];
}>();

// State to manage the visibility of the filters section
const filtersOpen = ref(false);
</script>

<template>
    <ContentWrapper>
        <div class="px-8 md:px-14 pt-8 pb-4">
            <h1
                class="text-5xl font-bold text-center mb-8 bg-linear-to-r from-primary to-secondary bg-clip-text text-transparent"
            >
                <slot name="title" />
            </h1>

            <div class="max-w-3xl mx-auto space-y-4">
                <div class="flex gap-3 items-stretch">
                    <label
                        class="input input-bordered input-lg flex items-center gap-2 flex-1 shadow-md"
                    >
                        <SearchIcon class="opacity-70 w-5 h-5" />
                        <slot name="search-input" />
                    </label>

                    <RouterLink
                        v-if="props.createRoute"
                        :to="props.createRoute"
                        class="btn btn-primary btn-lg gap-2"
                    >
                        <PlusIcon class="h-6 w-6" />
                        Create
                    </RouterLink>
                </div>

                <div
                    v-if="$slots.filters"
                    class="collapse collapse-arrow bg-base-200 rounded-box shadow-md overflow-visible relative z-50"
                    :class="{ 'collapse-open': filtersOpen, 'collapse-close': !filtersOpen }"
                >
                    <input type="checkbox" v-model="filtersOpen" class="peer" />
                    <div class="collapse-title text-lg font-semibold flex items-center gap-2 cursor-pointer">
                        <FilterIcon />
                        Advanced Filters
                    </div>
                    <div class="collapse-content overflow-visible">
                        <div class="flex flex-col gap-4 pt-4">
                            <slot name="filters" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="divider my-2"></div>

        <slot name="content" />
    </ContentWrapper>
</template>
