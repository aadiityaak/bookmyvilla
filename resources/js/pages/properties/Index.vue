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
    address: string | null;
    investor_name: string | null;
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

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Property"
                description="Manage properties (kost & villa)"
            />

            <Button v-if="canManage && !isInvestor" as-child>
                <Link href="/properties/create">Create property</Link>
            </Button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
            <div class="md:col-span-5">
                <Label for="q">Search</Label>
                <Input
                    id="q"
                    v-model="form.q"
                    placeholder="Name, address, investor"
                    @keyup.enter="applyFilters"
                />
            </div>

            <div class="md:col-span-3">
                <Label for="type">Type</Label>
                <select
                    id="type"
                    v-model="form.type"
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
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
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                    @change="applyFilters"
                >
                    <option value="">All</option>
                    <option v-for="s in statuses" :key="s" :value="s">
                        {{ s }}
                    </option>
                </select>
            </div>

            <div class="flex items-end gap-2 md:col-span-1">
                <Button
                    variant="outline"
                    class="w-full"
                    :disabled="!hasFilters"
                    @click="clearFilters"
                >
                    Clear
                </Button>
            </div>
        </div>

        <div class="rounded-xl border">
            <div
                class="grid grid-cols-12 gap-3 border-b px-4 py-3 text-sm font-medium"
            >
                <div class="col-span-3">Name</div>
                <div class="col-span-2">Type</div>
                <div class="col-span-3">Investor</div>
                <div class="col-span-2">Status</div>
                <div class="col-span-2 text-right">Action</div>
            </div>

            <div
                v-for="property in properties.data"
                :key="property.id"
                class="grid grid-cols-12 gap-3 px-4 py-3 text-sm"
            >
                <div class="col-span-3 truncate">
                    {{ property.name }}
                </div>
                <div class="col-span-2">
                    {{ property.type }}
                </div>
                <div class="col-span-3 truncate">
                    {{ property.investor_name ?? '-' }}
                </div>
                <div class="col-span-2">
                    {{ property.status }}
                </div>
                <div class="col-span-2 flex justify-end">
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

            <div v-if="properties.data.length === 0" class="px-4 py-6 text-sm">
                No properties found.
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Link
                v-for="link in properties.links"
                :key="link.label"
                :href="link.url ?? ''"
                class="rounded-md border px-3 py-1 text-sm"
                :class="[
                    link.active
                        ? 'bg-accent text-accent-foreground'
                        : 'bg-background',
                    !link.url ? 'pointer-events-none opacity-50' : '',
                ]"
            >
                <span v-html="link.label" />
            </Link>
            <span class="ml-auto text-sm text-muted-foreground">
                Total: {{ properties.total }}
            </span>
        </div>
    </div>
</template>

