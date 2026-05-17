<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { SidebarInset } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});
const className = computed(() => props.class);

const page = usePage();
const isMobileOnly = computed(() => {
    const user = (page.props as any)?.auth?.user ?? null;
    const role = user?.role ?? null;
    return !user || role === 'tenant';
});

const isScrolling = ref(false);
let scrollTimer: ReturnType<typeof setTimeout> | null = null;

const onScroll = () => {
    isScrolling.value = true;
    if (scrollTimer) clearTimeout(scrollTimer);
    scrollTimer = setTimeout(() => {
        isScrolling.value = false;
    }, 700);
};
</script>

<template>
    <SidebarInset v-if="props.variant === 'sidebar'" :class="className">
        <slot />
    </SidebarInset>
    <main
        v-else
        class="scroll-area flex min-h-0 w-full flex-1 flex-col overflow-y-auto"
        :class="[
            className,
            isScrolling ? 'scrolling' : '',
        ]"
        @scroll.passive="onScroll"
    >
        <div
            class="mx-auto flex min-h-0 w-full flex-1 flex-col gap-4 rounded-lg"
            :class="[isMobileOnly ? 'max-w-md' : 'max-w-7xl']"
        >
            <slot />
        </div>
    </main>
</template>
