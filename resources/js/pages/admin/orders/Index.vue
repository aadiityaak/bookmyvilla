<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type OrderRow = {
    id: number;
    property: { id: number; name: string; featured_image: string | null } | null;
    guest: { id: number; name: string; email: string } | null;
    check_in_date: string;
    check_out_date: string;
    guests_count: number;
    status: string;
    total_amount: string | null;
    currency: string;
    created_at: string | null;
    payment_proof_uploaded_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type OrdersPaginator = {
    data: OrderRow[];
    links: PaginationLink[];
    total: number;
};

const props = defineProps<{
    filters: {
        q?: string;
        status?: string;
    };
    orders: OrdersPaginator;
    statuses: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Orders',
                href: '/admin/orders',
            },
        ],
    },
});

const form = useForm({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
});

const hasFilters = computed(() => Boolean(form.q || form.status));

const applyFilters = () => {
    router.get('/admin/orders', form.data(), {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    form.q = '';
    form.status = '';
    applyFilters();
};

const imageUrl = (path: string | null) => {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const statusPillClass = (status: string) => {
    switch (status) {
        case 'confirmed':
            return 'bg-[color:var(--clay-brand-teal)] text-white border-transparent';
        case 'cancelled':
            return 'bg-[color:var(--clay-surface-strong)] text-[color:var(--clay-muted)] border-transparent';
        case 'pending_payment':
        default:
            return 'bg-[color:var(--clay-brand-peach)] text-black border-transparent';
    }
};
</script>

<template>
    <Head title="Orders" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 py-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <Heading
                variant="default"
                title="Orders"
                description="Kelola data order / booking"
            />

            <Badge
                variant="outline"
                class="bg-[color:var(--clay-surface-card)] text-[color:var(--clay-body)]"
            >
                {{ orders.total }} total
            </Badge>
        </div>

        <div class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                <div class="md:col-span-8">
                    <Label for="q">Search</Label>
                    <Input
                        id="q"
                        v-model="form.q"
                        class="clay-control"
                        placeholder="Order ID, property, guest"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="md:col-span-4">
                    <Label for="status">Status</Label>
                    <select
                        id="status"
                        v-model="form.status"
                        class="clay-select"
                        @change="applyFilters"
                    >
                        <option value="">All</option>
                        <option v-for="status in statuses" :key="status" :value="status">
                            {{ status }}
                        </option>
                    </select>
                </div>
            </div>

            <div v-if="hasFilters" class="mt-4 flex items-center justify-end gap-2">
                <Button variant="outline" @click="clearFilters"> Clear </Button>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]">
            <div class="grid grid-cols-12 gap-3 border-b border-[color:var(--clay-hairline)] px-5 py-3 text-xs font-medium uppercase tracking-wide text-[color:var(--clay-muted)]">
                <div class="col-span-5">Order</div>
                <div class="col-span-3">Guest</div>
                <div class="col-span-2">Status</div>
                <div class="col-span-2 text-right">Action</div>
            </div>

            <div
                v-for="o in orders.data"
                :key="o.id"
                class="grid grid-cols-12 gap-3 border-b border-[color:var(--clay-hairline)] px-5 py-4 text-sm last:border-b-0 hover:bg-[color:var(--clay-surface-soft)]"
            >
                <div class="col-span-5 flex min-w-0 items-start gap-3">
                    <div class="h-12 w-16 shrink-0 overflow-hidden rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)]">
                        <img
                            v-if="imageUrl(o.property?.featured_image ?? null)"
                            :src="imageUrl(o.property?.featured_image ?? null) ?? ''"
                            alt=""
                            class="h-full w-full object-cover"
                        />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium text-[color:var(--clay-ink)]">
                            #{{ o.id }} — {{ o.property?.name ?? '-' }}
                        </div>
                        <div class="mt-1 truncate text-xs text-[color:var(--clay-muted)]">
                            {{ o.check_in_date }} → {{ o.check_out_date }} • {{ o.guests_count }} tamu
                        </div>
                        <div v-if="o.total_amount" class="mt-1 truncate text-xs text-[color:var(--clay-muted)]">
                            {{ o.currency }} {{ o.total_amount }}
                        </div>
                    </div>
                </div>

                <div class="col-span-3 min-w-0">
                    <div class="truncate font-medium text-[color:var(--clay-ink)]">
                        {{ o.guest?.name ?? '-' }}
                    </div>
                    <div class="truncate text-xs text-[color:var(--clay-muted)]">
                        {{ o.guest?.email ?? '' }}
                    </div>
                </div>

                <div class="col-span-2">
                    <Badge :class="statusPillClass(o.status)">
                        {{ o.status }}
                    </Badge>
                    <div
                        v-if="o.payment_proof_uploaded_at"
                        class="mt-2 inline-flex items-center rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] px-2 py-1 text-xs text-[color:var(--clay-ink)]"
                    >
                        Bukti masuk
                    </div>
                </div>

                <div class="col-span-2 flex justify-end">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="`/admin/orders/${o.id}/edit`">Manage</Link>
                    </Button>
                </div>
            </div>

            <div v-if="orders.data.length === 0" class="px-5 py-10 text-sm text-[color:var(--clay-muted)]">
                No orders found.
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Link
                v-for="link in orders.links"
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
        </div>
    </div>
</template>
