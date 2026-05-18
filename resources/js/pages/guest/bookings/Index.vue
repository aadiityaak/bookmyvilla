<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

type BookingRow = {
    id: number;
    property: { id: number; name: string; featured_image: string | null } | null;
    check_in_date: string;
    check_out_date: string;
    guests_count: number;
    status: string;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type BookingsPaginator = {
    data: BookingRow[];
    links: PaginationLink[];
    total: number;
};

const props = defineProps<{
    bookings: BookingsPaginator;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Bookings',
                href: '/bookings',
            },
        ],
    },
});

const imageUrl = (path: string | null) => {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const page = usePage();
const isMyProperty = computed(() => page.url.startsWith('/my-property'));
const title = computed(() => (isMyProperty.value ? 'My Property' : 'My Bookings'));
const description = computed(() =>
    isMyProperty.value
        ? 'Riwayat properti yang sedang / pernah kamu sewa'
        : 'Daftar booking villa kamu',
);
</script>

<template>
    <Head :title="title" />

    <div class="mx-auto flex w-full max-w-md flex-col gap-6 px-4 py-6">
        <Heading variant="small" :title="title" :description="description" />

        <div class="space-y-3">
            <div
                v-for="b in bookings.data"
                :key="b.id"
                class="overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
            >
                <div class="flex items-start gap-3 p-3 sm:p-4">
                    <div
                        class="h-12 w-16 shrink-0 overflow-hidden rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)]"
                    >
                        <img
                            v-if="b.property?.featured_image && imageUrl(b.property.featured_image)"
                            :src="imageUrl(b.property.featured_image) ?? ''"
                            alt=""
                            class="h-full w-full object-cover"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <Link
                            v-if="b.property"
                            :href="`/explore/${b.property.id}`"
                            class="truncate font-medium text-[color:var(--clay-ink)]"
                        >
                            {{ b.property.name }}
                        </Link>
                        <div
                            v-else
                            class="truncate font-medium text-[color:var(--clay-ink)]"
                        >
                            -
                        </div>

                        <div class="mt-1 text-xs text-[color:var(--clay-muted)]">
                            #{{ b.id }}
                        </div>

                        <div class="mt-3 space-y-1 text-sm text-[color:var(--clay-body)]">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-[color:var(--clay-muted)]">Tanggal</div>
                                <div class="text-right">
                                    {{ b.check_in_date }} → {{ b.check_out_date }}
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-[color:var(--clay-muted)]">Tamu</div>
                                <div class="text-right">{{ b.guests_count }}</div>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-[color:var(--clay-muted)]">Status</div>
                                <div
                                    class="rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] px-2 py-1 text-xs text-[color:var(--clay-ink)]"
                                >
                                    {{ b.status }}
                                </div>
                            </div>
                            <div v-if="b.status === 'pending_payment'" class="pt-2">
                                <Button size="sm" class="w-full" as-child>
                                    <Link :href="`/bookings/${b.id}/payment`">Bayar sekarang</Link>
                                </Button>
                            </div>
                            <div v-else-if="b.status === 'confirmed'" class="pt-2">
                                <Button size="sm" class="w-full" as-child>
                                    <Link :href="`/bookings/${b.id}/ticket`">Lihat barcode</Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="bookings.data.length === 0"
                class="rounded-lg border border-dashed border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-6 text-sm text-[color:var(--clay-muted)]"
            >
                Belum ada booking.
            </div>
        </div>

        <div class="flex flex-col gap-3">
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                <Link
                    v-for="link in bookings.links"
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
