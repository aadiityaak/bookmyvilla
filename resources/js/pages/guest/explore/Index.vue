<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
        investor_id?: string;
        with_photo?: boolean;
        sort?: string;
    };
    properties: PropertiesPaginator;
    investors: { id: number; name: string }[];
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
    investor_id: props.filters.investor_id ?? '',
    with_photo: Boolean(props.filters.with_photo ?? false),
    sort: props.filters.sort ?? 'latest',
});

const hasFilters = computed(() => Boolean(form.q || form.investor_id || form.with_photo || form.sort !== 'latest'));

const applyFilters = () => {
    router.get('/explore', form.data(), { preserveState: true, replace: true });
};

const clearFilters = () => {
    form.q = '';
    form.investor_id = '';
    form.with_photo = false;
    form.sort = 'latest';
    applyFilters();
};

const imageUrl = (path: string | null) => {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
};
</script>

<template>
    <Head title="Explore" />

    <div class="mx-auto flex w-full max-w-md flex-col gap-6 px-4 py-6">
        <div class="flex flex-col items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Explore Villa"
                description="Cari villa dan buat booking harian"
            />
        </div>

        <div
            class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4"
        >
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <Label for="q" class="sr-only">Search</Label>
                    <Input
                        id="q"
                        v-model="form.q"
                        class="clay-control"
                        placeholder="Cari nama villa atau alamat…"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <Label for="investor_id" class="sr-only">Investor</Label>
                        <select
                            id="investor_id"
                            v-model="form.investor_id"
                            class="clay-select w-full"
                            @change="applyFilters"
                        >
                            <option value="">Semua investor</option>
                            <option v-for="inv in investors" :key="inv.id" :value="String(inv.id)">
                                {{ inv.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <Label for="sort" class="sr-only">Urutkan</Label>
                        <select
                            id="sort"
                            v-model="form.sort"
                            class="clay-select w-full"
                            @change="applyFilters"
                        >
                            <option value="latest">Terbaru</option>
                            <option value="name_asc">Nama A-Z</option>
                            <option value="name_desc">Nama Z-A</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <Label class="flex items-center gap-3 text-sm text-[color:var(--clay-body)]">
                        <Checkbox v-model:checked="form.with_photo" @update:checked="applyFilters" />
                        <span>Hanya yang ada foto</span>
                    </Label>
                    <Button class="shrink-0" variant="outline" @click="applyFilters">Terapkan</Button>
                </div>
            </div>

            <div v-if="hasFilters" class="mt-4 flex items-center justify-end gap-2">
                <Button variant="outline" @click="clearFilters">Reset</Button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4">
            <Link
                v-for="property in properties.data"
                :key="property.id"
                :href="`/explore/${property.id}`"
                class="group touch-manipulation overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] transition active:scale-[0.99]"
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

        <div class="flex flex-col gap-3">
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                <Link
                    v-for="link in properties.links"
                    :key="link.label"
                    :href="link.url ?? ''"
                    class="shrink-0 rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] px-3 py-1.5 text-sm"
                    :class="[
                        link.active
                            ? 'bg-[color:var(--clay-surface-card)] text-[color:var(--clay-ink)]'
                            : 'text-[color:var(--clay-body)]',
                        !link.url ? 'pointer-events-none opacity-50' : '',
                    ]"
                >
                    <span v-html="link.label" />
                </Link>
            </div>
        </div>
    </div>
</template>
