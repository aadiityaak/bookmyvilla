<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type PropertyCard = {
    id: number;
    type: string;
    name: string;
    featured_image: string | null;
    address: string | null;
    latitude: number | null;
    longitude: number | null;
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
        sort?: string;
        user_lat?: string;
        user_lng?: string;
    };
    properties: PropertiesPaginator;
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
    sort: props.filters.sort ?? 'latest',
});

const hasFilters = computed(() =>
    Boolean(form.q || form.sort !== 'latest'),
);

const userCoords = ref<{ lat: number; lng: number; accuracy: number | null } | null>(
    props.filters.user_lat && props.filters.user_lng
        ? {
              lat: Number(props.filters.user_lat),
              lng: Number(props.filters.user_lng),
              accuracy: null,
          }
        : null,
);

const getBrowserCoords = () => {
    return new Promise<{ lat: number; lng: number; accuracy: number | null }>(
        (resolve, reject) => {
        if (!('geolocation' in navigator)) {
            reject(new Error('geolocation_not_supported'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) =>
                resolve({
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    accuracy: Number.isFinite(pos.coords.accuracy)
                        ? pos.coords.accuracy
                        : null,
                }),
            (err) => reject(err),
            { enableHighAccuracy: true, maximumAge: 60_000, timeout: 10_000 },
        );
    },
    );
};

const applyFilters = async () => {
    const payload: Record<string, string> = {
        q: form.q ?? '',
        sort: form.sort ?? 'latest',
    };

    if (payload.sort === 'nearest') {
        if (!userCoords.value) {
            try {
                userCoords.value = await getBrowserCoords();
            } catch {
                userCoords.value = null;
            }
        }

        if (userCoords.value) {
            payload.user_lat = String(userCoords.value.lat);
            payload.user_lng = String(userCoords.value.lng);
        }
    }

    router.get('/explore', payload, { preserveState: true, replace: true });
};

const clearFilters = () => {
    form.q = '';
    form.sort = 'latest';
    userCoords.value = null;
    applyFilters();
};

const imageUrl = (path: string | null) => {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const isClient = ref(false);
const mapEl = ref<HTMLDivElement | null>(null);
let map: any = null;
let clusterLayer: any = null;
let userLayer: any = null;

const escapeHtml = (value: string) => {
    return value.replace(/[&<>"']/g, (ch) => {
        switch (ch) {
            case '&':
                return '&amp;';
            case '<':
                return '&lt;';
            case '>':
                return '&gt;';
            case '"':
                return '&quot;';
            case "'":
                return '&#39;';
            default:
                return ch;
        }
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
    await import('leaflet.markercluster');
    await import('leaflet.markercluster/dist/MarkerCluster.css');
    await import('leaflet.markercluster/dist/MarkerCluster.Default.css');

    const markerIcon2x = (
        await import('leaflet/dist/images/marker-icon-2x.png')
    ).default;
    const markerIcon = (await import('leaflet/dist/images/marker-icon.png'))
        .default;
    const markerShadow = (await import('leaflet/dist/images/marker-shadow.png'))
        .default;
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: markerIcon2x,
        iconUrl: markerIcon,
        shadowUrl: markerShadow,
    });

    map = L.map(mapEl.value, { zoomControl: true }).setView(
        [-8.670458, 115.212629],
        10,
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    clusterLayer = L.markerClusterGroup({
        showCoverageOnHover: false,
        spiderfyOnMaxZoom: true,
        disableClusteringAtZoom: 16,
        maxClusterRadius: 45,
    });
    map.addLayer(clusterLayer);

    userLayer = L.layerGroup();
    map.addLayer(userLayer);
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

const updateMarkers = async () => {
    await ensureMap();
    if (!map || !clusterLayer || !(window as any).__leaflet) return;

    const L = (window as any).__leaflet;
    clusterLayer.clearLayers();

    const points = props.properties.data.filter((p) =>
        isValidLatLng(p.latitude, p.longitude),
    );
    for (const p of points) {
        const lat = p.latitude as number;
        const lng = p.longitude as number;

        const title = escapeHtml(p.name);
        const address = escapeHtml(p.address ?? '-');
        const href = `/explore/${p.id}`;

        const popupHtml = `
            <div>
                <div style="font-weight: 600; margin-bottom: 2px;">${title}</div>
                <div style="font-size: 12px; opacity: 0.8; margin-bottom: 8px;">${address}</div>
                <a href="${href}" style="font-size: 12px; text-decoration: underline;">View details</a>
            </div>
        `.trim();

        const marker = L.marker([lat, lng]).bindPopup(popupHtml);
        clusterLayer.addLayer(marker);
    }

    if (points.length > 0) {
        const bounds = clusterLayer.getBounds();
        if (userCoords.value && isValidLatLng(userCoords.value.lat, userCoords.value.lng)) {
            bounds.extend([userCoords.value.lat, userCoords.value.lng]);
        }
        if (bounds.isValid()) {
            map.fitBounds(bounds.pad(0.2), { maxZoom: 15 });
        }
    } else if (userCoords.value && isValidLatLng(userCoords.value.lat, userCoords.value.lng)) {
        map.setView([userCoords.value.lat, userCoords.value.lng], Math.max(map.getZoom?.() ?? 14, 14), {
            animate: true,
        });
    }
};

onMounted(async () => {
    isClient.value = true;
    await nextTick();
    if (!userCoords.value) {
        try {
            userCoords.value = await getBrowserCoords();
        } catch {
            userCoords.value = null;
        }
    }
    await updateUserLocation();
    await updateMarkers();
});

watch(
    () => props.properties.data,
    async () => {
        if (!isClient.value) return;
        await nextTick();
        await updateMarkers();
    },
    { deep: true },
);

watch(
    () => userCoords.value,
    async () => {
        if (!isClient.value) return;
        await nextTick();
        await updateUserLocation();
        await updateMarkers();
    },
    { deep: true },
);

onBeforeUnmount(() => {
    if (map) {
        map.remove();
    }
    map = null;
    clusterLayer = null;
    userLayer = null;
});
</script>

<template>
    <Head title="Explore" />

    <div class="mx-auto flex w-full max-w-md flex-col gap-6 px-4 py-6">

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

                <div>
                    <Label for="sort" class="sr-only">Urutkan</Label>
                    <select
                        id="sort"
                        v-model="form.sort"
                        class="clay-select w-full"
                        @change="applyFilters"
                    >
                        <option value="latest">Terbaru</option>
                        <option value="nearest">Lokasi terdekat</option>
                        <option value="name_asc">Nama A-Z</option>
                        <option value="name_desc">Nama Z-A</option>
                    </select>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <Button
                        class="shrink-0"
                        variant="outline"
                        @click="applyFilters"
                        >Terapkan</Button
                    >
                </div>
            </div>

            <div
                v-if="hasFilters"
                class="mt-4 flex items-center justify-end gap-2"
            >
                <Button variant="outline" @click="clearFilters">Reset</Button>
            </div>
        </div>

        <div
            class="overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
        >
            <div ref="mapEl" class="h-64 w-full" />
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
                    <div
                        class="truncate font-medium text-[color:var(--clay-ink)]"
                    >
                        {{ property.name }}
                    </div>
                    <div
                        class="mt-1 truncate text-sm text-[color:var(--clay-muted)]"
                    >
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
