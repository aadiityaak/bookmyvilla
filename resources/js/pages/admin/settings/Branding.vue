<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Setting',
                href: '/admin/settings/branding',
            },
            {
                title: 'Branding',
                href: '/admin/settings/branding',
            },
        ],
    },
});
</script>

<template>
    <Head title="Setting - Branding" />

    <h1 class="sr-only">Setting Branding</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Branding"
            description="Atur identitas aplikasi: nama, logo, dan warna"
        />

        <div class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-5">
            <div class="text-sm text-[color:var(--clay-muted)]">
                Halaman ini menyiapkan UI pengaturan branding. Penyimpanan data bisa disambungkan ke backend setelah struktur field disepakati.
            </div>
        </div>
    </div>
</template>
