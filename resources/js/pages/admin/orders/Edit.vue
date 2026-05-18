<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Order = {
    id: number;
    status: string;
    check_in_date: string;
    check_out_date: string;
    guests_count: number;
    total_amount: string | null;
    currency: string;
    price_snapshot: any;
    created_at: string | null;
    updated_at: string | null;
    property: { id: number; name: string; featured_image: string | null; address: string | null } | null;
    guest: { id: number; name: string; email: string } | null;
};

const props = defineProps<{
    order: Order;
    statuses: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Orders', href: '/admin/orders' },
            { title: 'Manage', href: '/admin/orders' },
        ],
    },
});

const imageUrl = (path: string | null) => {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const form = useForm({
    status: props.order.status,
    total_amount: props.order.total_amount ?? '',
    currency: props.order.currency ?? 'IDR',
});

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

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'patch',
        total_amount: String(data.total_amount ?? '').trim() === '' ? null : data.total_amount,
    })).post(`/admin/orders/${props.order.id}`, { preserveScroll: true });
};

const propertyHref = computed(() => (props.order.property ? `/admin/properties/${props.order.property.id}/edit` : '/admin/properties'));
</script>

<template>
    <Head title="Manage order" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 py-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <Heading
                variant="small"
                title="Manage order"
                description="Update status dan informasi booking"
            />

            <div class="flex items-center gap-2">
                <Button variant="outline" as-child>
                    <Link href="/admin/orders">Back</Link>
                </Button>
                <Badge :class="statusPillClass(form.status)">
                    {{ form.status }}
                </Badge>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-12">
            <div class="md:col-span-4">
                <div class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4">
                    <div class="grid gap-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-[color:var(--clay-muted)]">Order ID</div>
                            <div class="font-medium text-[color:var(--clay-ink)]">#{{ props.order.id }}</div>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-[color:var(--clay-muted)]">Tanggal</div>
                            <div class="text-right font-medium text-[color:var(--clay-ink)]">
                                {{ props.order.check_in_date }} → {{ props.order.check_out_date }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-[color:var(--clay-muted)]">Tamu</div>
                            <div class="font-medium text-[color:var(--clay-ink)]">{{ props.order.guests_count }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4">
                    <div class="text-sm font-semibold">Property</div>
                    <Link
                        :href="propertyHref"
                        class="mt-3 flex items-start gap-3 rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-3"
                    >
                        <div class="h-12 w-16 shrink-0 overflow-hidden rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)]">
                            <img
                                v-if="imageUrl(props.order.property?.featured_image ?? null)"
                                :src="imageUrl(props.order.property?.featured_image ?? null) ?? ''"
                                alt=""
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-medium text-[color:var(--clay-ink)]">
                                {{ props.order.property?.name ?? '-' }}
                            </div>
                            <div class="mt-1 truncate text-xs text-[color:var(--clay-muted)]">
                                {{ props.order.property?.address ?? '-' }}
                            </div>
                        </div>
                    </Link>
                </div>

                <div class="mt-4 rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4">
                    <div class="text-sm font-semibold">Guest</div>
                    <div class="mt-3 rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-3">
                        <div class="truncate font-medium text-[color:var(--clay-ink)]">
                            {{ props.order.guest?.name ?? '-' }}
                        </div>
                        <div class="truncate text-xs text-[color:var(--clay-muted)]">
                            {{ props.order.guest?.email ?? '' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-8">
                <form
                    class="grid gap-6 rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-6"
                    @submit.prevent="submit"
                >
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                        <div class="grid gap-2 md:col-span-6">
                            <Label for="status">Status</Label>
                            <select id="status" v-model="form.status" class="clay-select">
                                <option v-for="status in props.statuses" :key="status" :value="status">
                                    {{ status }}
                                </option>
                            </select>
                            <InputError :message="form.errors.status" />
                        </div>

                        <div class="grid gap-2 md:col-span-6">
                            <Label for="currency">Currency</Label>
                            <Input id="currency" v-model="form.currency" class="clay-control" maxlength="3" />
                            <InputError :message="form.errors.currency" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="total_amount">Total amount</Label>
                        <Input
                            id="total_amount"
                            v-model="form.total_amount"
                            class="clay-control"
                            inputmode="decimal"
                            placeholder="contoh: 1500000"
                        />
                        <InputError :message="form.errors.total_amount" />
                    </div>

                    <div v-if="props.order.price_snapshot" class="grid gap-2">
                        <Label>Price snapshot</Label>
                        <pre class="overflow-x-auto rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-3 text-xs text-[color:var(--clay-body)]">{{ JSON.stringify(props.order.price_snapshot, null, 2) }}</pre>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <Button type="submit" :disabled="form.processing">Save</Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
