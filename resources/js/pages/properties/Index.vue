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
</script>

<template>
    <Head title="Property" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 py-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <Heading
                variant="small"
                title="Property"
                description="Manage properties"
            />

            <Button v-if="canManage && !isInvestor" as-child>
                <Link href="/properties/create">Create property</Link>
            </Button>
        </div>

        <div
            class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4"
        >
            <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                <div class="md:col-span-6">
                <Label for="q">Search</Label>
                <Input
                    id="q"
                    v-model="form.q"
                    class="clay-control"
                    placeholder="Name, address, investor"
                    @keyup.enter="applyFilters"
                />
            </div>

            <div class="md:col-span-3">
                <Label for="type">Type</Label>
                <select
                    id="type"
                    v-model="form.type"
                    class="clay-select"
                    @change="applyFilters"
                >
                    <option value="">All</option>
                    <option v-for="t in types" :key="t" :value="t">
                        {{ t }}
                    </option>
                </select>
            </div>

            <div class="md:col-span-3">
                <Label for="status">Status</Label>
                <select
                    id="status"
                    v-model="form.status"
                    class="clay-select"
                    @change="applyFilters"
                >
                    <option value="">All</option>
                    <option v-for="s in statuses" :key="s" :value="s">
                        {{ s }}
                    </option>
                </select>
            </div>
            </div>

            <div v-if="hasFilters" class="mt-4 flex items-center justify-end gap-2">
                <Button variant="outline" @click="clearFilters"> Clear </Button>
            </div>
        </div>

        <div
            class="overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
        >
            <div
                class="grid grid-cols-12 gap-3 border-b border-[color:var(--clay-hairline)] px-5 py-3 text-xs font-medium uppercase tracking-wide text-[color:var(--clay-muted)]"
            >
                <div class="col-span-4">Property</div>
                <div class="col-span-2">Type</div>
                <div class="col-span-3">Investor</div>
                <div class="col-span-2">Status</div>
                <div class="col-span-1 text-right">Action</div>
            </div>

            <div
                v-for="property in properties.data"
                :key="property.id"
                class="grid grid-cols-12 gap-3 border-b border-[color:var(--clay-hairline)] px-5 py-4 text-sm last:border-b-0 hover:bg-[color:var(--clay-surface-soft)]"
            >
                <div class="col-span-4 flex min-w-0 items-center gap-3">
                    <div
                        class="h-10 w-14 overflow-hidden rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)]"
                    >
                        <img
                            v-if="property.featured_image"
                            :src="
                                property.featured_image.startsWith('http')
                                    ? property.featured_image
                                    : `/storage/${property.featured_image}`
                            "
                            alt=""
                            class="h-full w-full object-cover"
                        />
                    </div>
                    <div class="min-w-0">
                        <div class="truncate font-medium text-[color:var(--clay-ink)]">
                            {{ property.name }}
                        </div>
                        <div class="truncate text-xs text-[color:var(--clay-muted)]">
                            {{ property.address ?? '-' }}
                        </div>
                    </div>
                </div>
                <div class="col-span-2 text-[color:var(--clay-body)]">
                    {{ property.type }}
                </div>
                <div class="col-span-3 truncate text-[color:var(--clay-body)]">
                    {{
                        property.investor
                            ? `${property.investor.name} — ${property.investor.role}`
                            : '-'
                    }}
                </div>
                <div class="col-span-2 text-[color:var(--clay-body)]">
                    {{ property.status }}
                </div>
                <div class="col-span-1 flex justify-end">
                    <Button
                        v-if="canManage && !isInvestor"
                        variant="ghost"
                        size="sm"
                        as-child
                    >
                        <Link :href="`/properties/${property.id}/edit`">
                            Edit
                        </Link>
                    </Button>
                    <span v-else class="text-muted-foreground">-</span>
                </div>
            </div>

            <div v-if="properties.data.length === 0" class="px-5 py-10 text-sm text-[color:var(--clay-muted)]">
                No properties found.
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
