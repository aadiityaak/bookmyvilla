<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type PropertyCard = {
    id: number;
    type: string;
    name: string;
    featured_image: string | null;
    address: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PropertiesPaginator = {
    data: PropertyCard[];
    links: PaginationLink[];
    total: number;
};

const props = defineProps<{
    filters: {
        q?: string;
    };
    properties: PropertiesPaginator;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Explore',
                href: '/explore',
            },
        ],
    },
});

const form = useForm({
    q: props.filters.q ?? '',
});

const hasFilters = computed(() => Boolean(form.q));

const applyFilters = () => {
    router.get('/explore', form.data(), { preserveState: true, replace: true });
};

const clearFilters = () => {
    form.q = '';
    applyFilters();
};

const imageUrl = (path: string | null) => {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
};
</script>

<template>
    <Head title="Explore" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 py-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <Heading
                variant="small"
                title="Explore Villa"
                description="Cari villa dan buat booking harian"
            />
        </div>

        <div
            class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4"
        >
            <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                <div class="md:col-span-10">
                    <Label for="q">Search</Label>
                    <Input
                        id="q"
                        v-model="form.q"
                        class="clay-control"
                        placeholder="Name or address"
                        @keyup.enter="applyFilters"
                    />
                </div>
                <div class="md:col-span-2 md:flex md:items-end">
                    <Button class="w-full" @click="applyFilters">Search</Button>
                </div>
            </div>

            <div v-if="hasFilters" class="mt-4 flex items-center justify-end gap-2">
                <Button variant="outline" @click="clearFilters">Clear</Button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <Link
                v-for="property in properties.data"
                :key="property.id"
                :href="`/explore/${property.id}`"
                class="group overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
            >
                <div
                    class="aspect-[16/9] w-full overflow-hidden bg-[color:var(--clay-surface-card)]"
                >
                    <img
                        v-if="imageUrl(property.featured_image)"
                        :src="imageUrl(property.featured_image) ?? ''"
                        alt=""
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
                    />
                </div>
                <div class="p-4">
                    <div class="truncate font-medium text-[color:var(--clay-ink)]">
                        {{ property.name }}
                    </div>
                    <div class="mt-1 truncate text-sm text-[color:var(--clay-muted)]">
                        {{ property.address ?? '-' }}
                    </div>
                    <div class="mt-3 text-sm text-[color:var(--clay-body)]">
                        View details →
                    </div>
                </div>
            </Link>

            <div
                v-if="properties.data.length === 0"
                class="rounded-lg border border-dashed border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-6 text-sm text-[color:var(--clay-muted)]"
            >
                No villas found.
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Link
                v-for="link in properties.links"
                :key="link.label"
                :href="link.url ?? ''"
                class="rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] px-3 py-1.5 text-sm"
                :class="[
                    link.active
                        ? 'bg-[color:var(--clay-surface-card)] text-[color:var(--clay-ink)]'
                        : 'text-[color:var(--clay-body)]',
                    !link.url ? 'pointer-events-none opacity-50' : '',
                ]"
            >
                <span v-html="link.label" />
            </Link>
            <span class="ml-auto text-sm text-[color:var(--clay-muted)]">
                Total: {{ properties.total }}
            </span>
        </div>
    </div>
</template>

