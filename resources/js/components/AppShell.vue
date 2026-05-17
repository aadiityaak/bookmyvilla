<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
};

withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});

const page = usePage();
const isOpen = page.props.sidebarOpen;

const normalizeHex = (value: unknown): string | null => {
    if (typeof value !== 'string') {
        return null;
    }

    const trimmed = value.trim();

    if (!/^#([0-9a-fA-F]{6})$/.test(trimmed)) {
        return null;
    }

    return trimmed.toLowerCase();
};

const toForeground = (hex: string): string => {
    const r = parseInt(hex.slice(1, 3), 16) / 255;
    const g = parseInt(hex.slice(3, 5), 16) / 255;
    const b = parseInt(hex.slice(5, 7), 16) / 255;

    const toLinear = (c: number) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4);
    const rl = toLinear(r);
    const gl = toLinear(g);
    const bl = toLinear(b);
    const luminance = 0.2126 * rl + 0.7152 * gl + 0.0722 * bl;

    return luminance > 0.6 ? '#0a0a0a' : '#ffffff';
};

const branding = computed(() => (page.props as any)?.branding ?? null);
const primary = computed(() => normalizeHex(branding.value?.primary_color));
const secondary = computed(() => normalizeHex(branding.value?.secondary_color));

const brandStyle = computed<Record<string, string>>(() => {
    const vars: Record<string, string> = {};

    if (primary.value) {
        const fg = toForeground(primary.value);
        vars['--primary'] = primary.value;
        vars['--ring'] = primary.value;
        vars['--primary-foreground'] = fg;
        vars['--sidebar-primary'] = primary.value;
        vars['--sidebar-ring'] = primary.value;
        vars['--sidebar-primary-foreground'] = fg;
        vars['--clay-brand-teal'] = primary.value;
    }

    if (secondary.value) {
        vars['--chart-1'] = secondary.value;
        vars['--clay-brand-pink'] = secondary.value;
    }

    return vars;
});
</script>

<template>
    <div
        v-if="variant === 'header'"
        class="flex h-dvh w-full flex-col overflow-hidden"
        :style="brandStyle"
    >
        <slot />
    </div>
    <SidebarProvider v-else :default-open="isOpen" :style="brandStyle">
        <slot />
    </SidebarProvider>
</template>
