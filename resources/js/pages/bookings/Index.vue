<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';

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
</script>

<template>
    <Head title="Bookings" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 py-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <Heading
                variant="small"
                title="My Bookings"
                description="Daftar booking villa kamu"
            />
        </div>

        <div
            class="overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
        >
            <div
                class="grid grid-cols-12 gap-3 border-b border-[color:var(--clay-hairline)] px-5 py-3 text-xs font-medium uppercase tracking-wide text-[color:var(--clay-muted)]"
            >
                <div class="col-span-5">Property</div>
                <div class="col-span-3">Dates</div>
                <div class="col-span-2">Guests</div>
                <div class="col-span-2">Status</div>
            </div>

            <div
                v-for="b in bookings.data"
                :key="b.id"
                class="grid grid-cols-12 gap-3 border-b border-[color:var(--clay-hairline)] px-5 py-4 text-sm last:border-b-0 hover:bg-[color:var(--clay-surface-soft)]"
            >
                <div class="col-span-5 flex min-w-0 items-center gap-3">
                    <div
                        class="h-10 w-14 overflow-hidden rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)]"
                    >
                        <img
                            v-if="b.property?.featured_image && imageUrl(b.property.featured_image)"
                            :src="imageUrl(b.property.featured_image) ?? ''"
                            alt=""
                            class="h-full w-full object-cover"
                        />
                    </div>
                    <div class="min-w-0">
                        <Link
                            v-if="b.property"
                            :href="`/explore/${b.property.id}`"
                            class="truncate font-medium text-[color:var(--clay-ink)] hover:underline"
                        >
                            {{ b.property.name }}
                        </Link>
                        <div v-else class="truncate font-medium text-[color:var(--clay-ink)]">
                            -
                        </div>
                        <div class="truncate text-xs text-[color:var(--clay-muted)]">
                            #{{ b.id }}
                        </div>
                    </div>
                </div>
                <div class="col-span-3 text-[color:var(--clay-body)]">
                    {{ b.check_in_date }} → {{ b.check_out_date }}
                </div>
                <div class="col-span-2 text-[color:var(--clay-body)]">
                    {{ b.guests_count }}
                </div>
                <div class="col-span-2 text-[color:var(--clay-body)]">
                    {{ b.status }}
                </div>
            </div>

            <div
                v-if="bookings.data.length === 0"
                class="px-5 py-10 text-sm text-[color:var(--clay-muted)]"
            >
                Belum ada booking.
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Link
                v-for="link in bookings.links"
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
                Total: {{ bookings.total }}
            </span>
        </div>
    </div>
</template>

