<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
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
    latitude: number | null;
    longitude: number | null;
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

const isClient = ref(false);
const mapEl = ref<HTMLDivElement | null>(null);
let map: any = null;
let propertyLayer: any = null;
let userLayer: any = null;

const userCoords = ref<{ lat: number; lng: number; accuracy: number | null } | null>(null);

const getBrowserCoords = () => {
    return new Promise<{ lat: number; lng: number; accuracy: number | null }>((resolve, reject) => {
        if (!('geolocation' in navigator)) {
            reject(new Error('geolocation_not_supported'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) =>
                resolve({
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    accuracy: Number.isFinite(pos.coords.accuracy) ? pos.coords.accuracy : null,
                }),
            (err) => reject(err),
            { enableHighAccuracy: true, maximumAge: 60_000, timeout: 10_000 },
        );
    });
};

const isValidLatLng = (lat: number | null, lng: number | null) => {
    if (lat === null || lng === null) return false;
    return lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
};

const ensureMap = async () => {
    if (typeof window === 'undefined') return;
    if (!mapEl.value) return;
    if (map) return;

    const leafletModule = await import('leaflet');
    const L: any = (leafletModule as any).default ?? leafletModule;
    (window as any).__leaflet = L;
    await import('leaflet/dist/leaflet.css');

    const markerIcon2x = (await import('leaflet/dist/images/marker-icon-2x.png')).default;
    const markerIcon = (await import('leaflet/dist/images/marker-icon.png')).default;
    const markerShadow = (await import('leaflet/dist/images/marker-shadow.png')).default;
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: markerIcon2x,
        iconUrl: markerIcon,
        shadowUrl: markerShadow,
    });

    const hasPropertyCoords = isValidLatLng(props.property.latitude, props.property.longitude);
    const center = hasPropertyCoords
        ? ([props.property.latitude, props.property.longitude] as [number, number])
        : ([-7.797068, 110.370529] as [number, number]);
    const zoom = hasPropertyCoords ? 14 : 12;

    map = L.map(mapEl.value, { zoomControl: true }).setView(center, zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    propertyLayer = L.layerGroup();
    userLayer = L.layerGroup();
    map.addLayer(propertyLayer);
    map.addLayer(userLayer);
};

const updatePropertyMarker = async () => {
    await ensureMap();
    if (!map || !propertyLayer || !(window as any).__leaflet) return;

    const L = (window as any).__leaflet;
    propertyLayer.clearLayers();

    if (!isValidLatLng(props.property.latitude, props.property.longitude)) return;
    const lat = props.property.latitude as number;
    const lng = props.property.longitude as number;

    const popupHtml = `
        <div>
            <div style="font-weight: 600; margin-bottom: 2px;">${props.property.name}</div>
            <div style="font-size: 12px; opacity: 0.8;">${props.property.address ?? '-'}</div>
        </div>
    `.trim();

    L.marker([lat, lng]).addTo(propertyLayer).bindPopup(popupHtml);
};

const updateUserLocation = async () => {
    await ensureMap();
    if (!map || !userLayer || !(window as any).__leaflet) return;

    const L = (window as any).__leaflet;
    userLayer.clearLayers();

    if (!userCoords.value) return;
    if (!isValidLatLng(userCoords.value.lat, userCoords.value.lng)) return;

    const lat = userCoords.value.lat;
    const lng = userCoords.value.lng;
    const accuracy = userCoords.value.accuracy;

    if (typeof accuracy === 'number' && accuracy > 0) {
        L.circle([lat, lng], {
            radius: accuracy,
            color: '#2563eb',
            weight: 1,
            fillColor: '#3b82f6',
            fillOpacity: 0.12,
        }).addTo(userLayer);
    }

    L.circleMarker([lat, lng], {
        radius: 7,
        color: '#1d4ed8',
        weight: 2,
        fillColor: '#3b82f6',
        fillOpacity: 0.9,
    })
        .addTo(userLayer)
        .bindPopup('Lokasi saya');
};

const fitMap = async () => {
    await ensureMap();
    if (!map || !(window as any).__leaflet) return;

    const L = (window as any).__leaflet;
    const bounds = L.latLngBounds([]);

    if (isValidLatLng(props.property.latitude, props.property.longitude)) {
        bounds.extend([props.property.latitude as number, props.property.longitude as number]);
    }
    if (userCoords.value && isValidLatLng(userCoords.value.lat, userCoords.value.lng)) {
        bounds.extend([userCoords.value.lat, userCoords.value.lng]);
    }

    if (bounds.isValid()) {
        map.fitBounds(bounds.pad(0.2), { maxZoom: 15 });
    }
};

onMounted(async () => {
    isClient.value = true;
    await nextTick();
    await updatePropertyMarker();

    try {
        userCoords.value = await getBrowserCoords();
    } catch {
        userCoords.value = null;
    }

    await updateUserLocation();
    await fitMap();
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
    }
    map = null;
    propertyLayer = null;
    userLayer = null;
});
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
                    class="mt-6 overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
                >
                    <div ref="mapEl" class="h-64 w-full" />
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
