<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

const page = usePage();
const user = computed(() => (page.props as any)?.auth?.user ?? null);
const avatarUrl = computed(() => {
    const path = user.value?.avatar;
    if (!path) return null;
    return String(path).startsWith('http') ? String(path) : `/storage/${path}`;
});
</script>

<template>
    <Head title="Akun" />

    <div class="flex w-full flex-col gap-6 px-4 py-6">
        <Heading
            variant="small"
            title="Akun"
            description="Kelola profil dan lihat riwayat sewa"
        />

        <div class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-4">
            <div class="flex items-center gap-3">
                <div
                    class="h-12 w-12 overflow-hidden rounded-full border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)]"
                >
                    <img v-if="avatarUrl" :src="avatarUrl" alt="" class="h-full w-full object-cover" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="truncate font-medium text-[color:var(--clay-ink)]">
                        {{ user?.name ?? '-' }}
                    </div>
                    <div class="truncate text-sm text-[color:var(--clay-muted)]">
                        {{ user?.email ?? '-' }}
                    </div>
                </div>
                <Button size="sm" variant="outline" as-child>
                    <Link href="/profile">Edit</Link>
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3">
            <Link
                href="/my-property"
                class="flex items-center justify-between rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-4 text-sm"
            >
                <div class="font-medium text-[color:var(--clay-ink)]">My Property</div>
                <div class="text-[color:var(--clay-muted)]">Riwayat sewa →</div>
            </Link>

            <Link
                href="/profile"
                class="flex items-center justify-between rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-4 text-sm"
            >
                <div class="font-medium text-[color:var(--clay-ink)]">Profil</div>
                <div class="text-[color:var(--clay-muted)]">Edit data diri →</div>
            </Link>
        </div>
    </div>
</template>
