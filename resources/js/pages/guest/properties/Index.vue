<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type PropertyRow = {
    id: number;
    type: string;
    name: string;
    featured_image: string | null;
    address: string | null;
    investor: { id: number; name: string; email: string; role: string } | null;
    status: string;
    owner: { id: number; name: string; email: string; role: string } | null;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PropertiesPaginator = {
    data: PropertyRow[];
    links: PaginationLink[];
    total: number;
};

const props = defineProps<{
    filters: {
        q?: string;
        type?: string;
        status?: string;
    };
    properties: PropertiesPaginator;
    types: string[];
    statuses: string[];
    canManage: boolean;
    canManageAll: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Property',
                href: '/properties',
            },
        ],
    },
});

const page = usePage();
const role = computed(() => (page.props.auth?.user as any)?.role);
const isInvestor = computed(() => role.value === 'investor');

const form = useForm({
    q: props.filters.q ?? '',
    type: props.filters.type ?? '',
    status: props.filters.status ?? '',
});

const hasFilters = computed(() => Boolean(form.q || form.type || form.status));

const applyFilters = () => {
    router.get('/properties', form.data(), {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    form.q = '';
    form.type = '';
    form.status = '';
    applyFilters();
};

const imageUrl = (path: string | null) => {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
};
</script>

<template>
    <Head title="Property" />

    <div class="mx-auto flex w-full max-w-md flex-col gap-6 px-4 py-6">
        <Heading
            variant="small"
            title="Property"
            description="Kelola listing kost & villa"
        />

        <Button v-if="canManage && !isInvestor" class="w-full" as-child>
            <Link href="/properties/create">Buat Property</Link>
        </Button>

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
                        placeholder="Cari nama / alamat / investor…"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <Label for="type" class="sr-only">Type</Label>
                        <select
                            id="type"
                            v-model="form.type"
                            class="clay-select w-full"
                            @change="applyFilters"
                        >
                            <option value="">Semua tipe</option>
                            <option v-for="t in types" :key="t" :value="t">
                                {{ t }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <Label for="status" class="sr-only">Status</Label>
                        <select
                            id="status"
                            v-model="form.status"
                            class="clay-select w-full"
                            @change="applyFilters"
                        >
                            <option value="">Semua status</option>
                            <option v-for="s in statuses" :key="s" :value="s">
                                {{ s }}
                            </option>
                        </select>
                    </div>
                </div>

                <Button class="w-full" @click="applyFilters">Terapkan Filter</Button>

            <div v-if="hasFilters" class="mt-2 flex items-center justify-end gap-2">
                <Button variant="outline" @click="clearFilters">Reset</Button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4">
            <div
                v-for="property in properties.data"
                :key="property.id"
                class="overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
            >
                <div class="flex items-start gap-3 p-4">
                    <div
                        class="h-12 w-16 shrink-0 overflow-hidden rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)]"
                    >
                        <img
                            v-if="imageUrl(property.featured_image)"
                            :src="imageUrl(property.featured_image) ?? ''"
                            alt=""
                            class="h-full w-full object-cover"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium text-[color:var(--clay-ink)]">
                            {{ property.name }}
                        </div>
                        <div class="mt-1 truncate text-sm text-[color:var(--clay-muted)]">
                            {{ property.address ?? '-' }}
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                            <span
                                class="rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] px-2 py-1 text-[color:var(--clay-ink)]"
                            >
                                {{ property.type }}
                            </span>
                            <span
                                class="rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] px-2 py-1 text-[color:var(--clay-ink)]"
                            >
                                {{ property.status }}
                            </span>
                            <span
                                v-if="property.investor"
                                class="truncate text-[color:var(--clay-muted)]"
                            >
                                {{ property.investor.name }} — {{ property.investor.role }}
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between gap-3 border-t border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-soft)] px-4 py-3"
                >
                    <div class="text-xs text-[color:var(--clay-muted)]">
                        ID: {{ property.id }}
                    </div>
                    <Button
                        v-if="canManage && !isInvestor"
                        size="sm"
                        variant="outline"
                        as-child
                    >
                        <Link :href="`/properties/${property.id}/edit`">Edit</Link>
                    </Button>
                </div>
            </div>

            <div
                v-if="properties.data.length === 0"
                class="rounded-lg border border-dashed border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-6 text-sm text-[color:var(--clay-muted)]"
            >
                Belum ada property.
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
