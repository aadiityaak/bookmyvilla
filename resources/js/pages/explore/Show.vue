<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type PropertyDetail = {
    id: number;
    type: string;
    name: string;
    featured_image: string | null;
    address: string | null;
    description: string | null;
    gallery: string[];
    status: string;
};

type BlockedRange = {
    check_in_date: string;
    check_out_date: string;
    status: string;
};

const props = defineProps<{
    property: PropertyDetail;
    blocked: BlockedRange[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Explore',
                href: '/explore',
            },
            {
                title: 'Detail',
                href: '#',
            },
        ],
    },
});

const page = usePage();
const role = computed(() => (page.props.auth?.user as any)?.role);
const canBook = computed(() => role.value === 'tenant');

const imageUrl = (path: string | null) => {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const galleryImages = computed(() => {
    const images = [
        props.property.featured_image,
        ...(props.property.gallery ?? []),
    ].filter(Boolean) as string[];

    const normalized = images.map((p) => imageUrl(p) ?? '').filter(Boolean);
    return Array.from(new Set(normalized));
});

const form = useForm({
    property_id: props.property.id,
    check_in_date: '',
    check_out_date: '',
    guests_count: 1,
});

const submit = () => {
    form.post('/bookings', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="property.name" />

    <div class="mx-auto flex w-full max-w-md flex-col gap-8 px-6 py-8">
        <div class="flex flex-col items-start justify-between gap-4">
            <div class="min-w-0">
                <Heading
                    variant="small"
                    :title="property.name"
                    :description="property.address ?? '—'"
                />
            </div>
            <Button variant="outline" as-child>
                <Link href="/explore">Back</Link>
            </Button>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <div>
                <div
                    class="overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
                >
                    <div
                        class="aspect-[16/9] w-full overflow-hidden bg-[color:var(--clay-surface-card)]"
                    >
                        <img
                            v-if="galleryImages[0]"
                            :src="galleryImages[0]"
                            alt=""
                            class="h-full w-full object-cover"
                        />
                    </div>
                    <div v-if="galleryImages.length > 1" class="grid grid-cols-4 gap-2 p-4">
                        <div
                            v-for="src in galleryImages.slice(1, 9)"
                            :key="src"
                            class="aspect-[4/3] overflow-hidden rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)]"
                        >
                            <img :src="src" alt="" class="h-full w-full object-cover" />
                        </div>
                    </div>
                </div>

                <div
                    class="mt-6 rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-5"
                >
                    <div class="text-sm font-medium text-[color:var(--clay-ink)]">
                        Description
                    </div>
                    <div
                        v-if="property.description"
                        class="prose prose-sm mt-3 max-w-none text-[color:var(--clay-body)]"
                        v-html="property.description"
                    />
                    <div
                        v-else
                        class="mt-3 text-sm text-[color:var(--clay-muted)]"
                    >
                        No description.
                    </div>
                </div>

                <div
                    v-if="blocked.length"
                    class="mt-6 rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-5"
                >
                    <div class="text-sm font-medium text-[color:var(--clay-ink)]">
                        Booked Dates
                    </div>
                    <div class="mt-3 space-y-2 text-sm text-[color:var(--clay-body)]">
                        <div
                            v-for="(b, idx) in blocked"
                            :key="idx"
                            class="flex items-center justify-between rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] px-3 py-2"
                        >
                            <div>{{ b.check_in_date }} → {{ b.check_out_date }}</div>
                            <div class="text-xs text-[color:var(--clay-muted)]">
                                {{ b.status }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div
                    class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-5"
                >
                    <div class="text-sm font-medium text-[color:var(--clay-ink)]">
                        Booking
                    </div>

                    <div v-if="!canBook" class="mt-3 text-sm text-[color:var(--clay-muted)]">
                        Login sebagai tenant untuk membuat booking.
                    </div>

                    <form v-else class="mt-4 space-y-4" @submit.prevent="submit">
                        <div>
                            <Label for="check_in_date">Check-in</Label>
                            <Input
                                id="check_in_date"
                                v-model="form.check_in_date"
                                class="clay-control"
                                type="date"
                            />
                            <InputError :message="form.errors.check_in_date" />
                        </div>

                        <div>
                            <Label for="check_out_date">Check-out</Label>
                            <Input
                                id="check_out_date"
                                v-model="form.check_out_date"
                                class="clay-control"
                                type="date"
                            />
                            <InputError :message="form.errors.check_out_date" />
                        </div>

                        <div>
                            <Label for="guests_count">Guests</Label>
                            <Input
                                id="guests_count"
                                v-model="form.guests_count"
                                class="clay-control"
                                type="number"
                                min="1"
                                max="30"
                            />
                            <InputError :message="form.errors.guests_count" />
                        </div>

                        <Button class="w-full" type="submit" :disabled="form.processing">
                            Create booking
                        </Button>
                    </form>
                </div>

                <div
                    class="mt-6 rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-5 text-sm text-[color:var(--clay-muted)]"
                >
                    Status booking awal: pending_payment (sementara, sebelum integrasi pembayaran).
                </div>
            </div>
        </div>
    </div>
</template>
