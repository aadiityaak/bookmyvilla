<script setup lang="ts">
import { computed } from 'vue';
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
</script>

<template>
    <SidebarInset v-if="props.variant === 'sidebar'" :class="className">
        <slot />
    </SidebarInset>
    <main
        v-else
        class="mx-auto flex h-full w-full flex-1 flex-col gap-4 rounded-lg"
        :class="[isMobileOnly ? 'max-w-md' : 'max-w-7xl', className]"
    >
        <slot />
    </main>
</template>
