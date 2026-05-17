<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Splide, SplideSlide } from '@splidejs/vue-splide';
import { CalendarDays, Image, MapPinned, Users } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import '@splidejs/vue-splide/css';

type PropertyDetail = {
    id: number;
    type: string;
    name: string;
    featured_image: string | null;
    address: string | null;
    latitude: number | null;
    longitude: number | null;
    description: string | null;
    amenities: string | null;
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
    if (!path) {
        return null;
    }

    if (path.startsWith('http')) {
        return path;
    }

    return `/storage/${path}`;
};

const galleryImages = computed(() => {
    const images = [
        props.property.featured_image,
        ...(props.property.gallery ?? []),
    ].filter(Boolean) as string[];

    const normalized = images.map((p) => imageUrl(p) ?? '').filter(Boolean);

    return Array.from(new Set(normalized));
});

const heroImage = computed(() => galleryImages.value[0] ?? null);

const mainGalleryRef = ref<any>(null);
const thumbsGalleryRef = ref<any>(null);

const syncGallery = async () => {
    await nextTick();

    const main = (mainGalleryRef.value as any)?.splide ?? mainGalleryRef.value;
    const thumbs = (thumbsGalleryRef.value as any)?.splide ?? thumbsGalleryRef.value;

    if (!main || !thumbs || typeof main.sync !== 'function') {
        return;
    }

    main.sync(thumbs);
};

const amenitiesList = computed(() => {
    const raw = props.property.amenities ?? '';

    if (!raw.trim()) {
        return [];
    }

    return raw
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean)
        .map((line) => line.replace(/^[+\-•]\s*/, '').trim())
        .filter(Boolean);
});

const form = useForm({
    property_id: props.property.id,
    check_in_date: '',
    check_out_date: '',
    guests_count: 1,
});

const quickNavRef = ref<HTMLDivElement | null>(null);

const scrollToSection = (id: string) => {
    if (!isClient.value) {
        return;
    }

    const target = document.getElementById(id);
    if (!target) {
        return;
    }

    const navHeight = quickNavRef.value?.getBoundingClientRect().height ?? 0;
    const extraOffset = 12;

    const scrollArea = document.querySelector<HTMLElement>('.scroll-area');
    if (scrollArea) {
        const containerRect = scrollArea.getBoundingClientRect();
        const targetRect = target.getBoundingClientRect();
        const top =
            scrollArea.scrollTop +
            (targetRect.top - containerRect.top) -
            navHeight -
            extraOffset;

        scrollArea.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
        window.history.replaceState(null, '', `#${id}`);

        return;
    }

    const top = target.getBoundingClientRect().top + window.scrollY - navHeight - extraOffset;
    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
    window.history.replaceState(null, '', `#${id}`);
};

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
    if (lat === null || lng === null) {
        return false;
    }

    return lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
};

const getCssVarColor = (name: string, fallback: string) => {
    if (typeof window === 'undefined') {
        return fallback;
    }

    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    if (!value) {
        return fallback;
    }

    return value;
};

const ensureMap = async () => {
    if (typeof window === 'undefined') {
        return;
    }

    if (!mapEl.value) {
        return;
    }

    if (map) {
        return;
    }

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

    if (!map || !propertyLayer || !(window as any).__leaflet) {
        return;
    }

    const L = (window as any).__leaflet;
    propertyLayer.clearLayers();

    if (!isValidLatLng(props.property.latitude, props.property.longitude)) {
        return;
    }

    const lat = props.property.latitude as number;
    const lng = props.property.longitude as number;

    const primary = getCssVarColor('--primary', '#0a0a0a');
    const popupHtml = `
        <div>
            <div style="font-weight: 600; margin-bottom: 2px;">${props.property.name}</div>
            <div style="font-size: 12px; opacity: 0.8;">${props.property.address ?? '-'}</div>
        </div>
    `.trim();

    L.circleMarker([lat, lng], {
        radius: 8,
        color: primary,
        weight: 2,
        fillColor: primary,
        fillOpacity: 0.9,
    })
        .addTo(propertyLayer)
        .bindPopup(popupHtml);
};

const updateUserLocation = async () => {
    await ensureMap();

    if (!map || !userLayer || !(window as any).__leaflet) {
        return;
    }

    const L = (window as any).__leaflet;
    userLayer.clearLayers();

    if (!userCoords.value) {
        return;
    }

    if (!isValidLatLng(userCoords.value.lat, userCoords.value.lng)) {
        return;
    }

    const lat = userCoords.value.lat;
    const lng = userCoords.value.lng;
    const accuracy = userCoords.value.accuracy;
    const primary = getCssVarColor('--primary', '#0a0a0a');

    if (typeof accuracy === 'number' && accuracy > 0) {
        L.circle([lat, lng], {
            radius: accuracy,
            color: primary,
            weight: 1,
            fillColor: primary,
            fillOpacity: 0.12,
        }).addTo(userLayer);
    }

    L.circleMarker([lat, lng], {
        radius: 7,
        color: primary,
        weight: 2,
        fillColor: primary,
        fillOpacity: 0.9,
    })
        .addTo(userLayer)
        .bindPopup('Lokasi saya');
};

const fitMap = async () => {
    await ensureMap();

    if (!map || !(window as any).__leaflet) {
        return;
    }

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

    const initialHash = window.location.hash?.replace('#', '').trim();
    if (initialHash) {
        scrollToSection(initialHash);
    }

    if (galleryImages.value.length > 1) {
        await syncGallery();
    }

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

    <div class="min-h-dvh bg-[color:var(--clay-canvas)] text-[color:var(--clay-ink)]">
        <div class="mx-auto w-full max-w-md px-4 pb-[calc(6.5rem+env(safe-area-inset-bottom))] pt-6">
            <div class="relative overflow-hidden rounded-2xl bg-[color:var(--clay-surface-card)]">
                <div class="relative aspect-[16/10] w-full">
                    <img
                        v-if="heroImage"
                        :src="heroImage"
                        alt=""
                        class="absolute inset-0 h-full w-full object-cover"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-black/20 to-transparent" />
                    <div class="absolute inset-0 flex flex-col justify-between p-4">
                        <div class="flex items-start justify-between gap-3">
                            <Button
                                size="sm"
                                variant="outline"
                                class="border-white/25 bg-white/10 text-white hover:bg-white/20 hover:text-white"
                                as-child
                            >
                                <Link href="/explore">Kembali</Link>
                            </Button>
                            <div
                                class="rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-medium text-white backdrop-blur-sm"
                            >
                                {{ property.type === 'kost' ? 'Kost' : 'Villa' }}
                            </div>
                        </div>
                        <div class="min-w-0">
                            <div class="truncate text-lg font-semibold tracking-tight text-white">
                                {{ property.name }}
                            </div>
                            <div class="mt-1 truncate text-sm text-white/80">
                                {{ property.address ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="-mx-4 mt-5">
                <div
                    ref="quickNavRef"
                    class="sticky top-0 z-[1900] border-b border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] px-4 py-2"
                >
                    <div class="flex items-center gap-2 overflow-x-auto pb-1">
                        <a
                            href="#photos"
                            class="shrink-0 rounded-full bg-[color:var(--clay-surface-soft)] px-3 py-1.5 text-sm text-[color:var(--clay-body)]"
                            @click.prevent="scrollToSection('photos')"
                        >
                            <span class="inline-flex items-center gap-2">
                                <Image class="h-4 w-4" />
                                Foto
                            </span>
                        </a>
                        <a
                            href="#map"
                            class="shrink-0 rounded-full bg-[color:var(--clay-surface-soft)] px-3 py-1.5 text-sm text-[color:var(--clay-body)]"
                            @click.prevent="scrollToSection('map')"
                        >
                            <span class="inline-flex items-center gap-2">
                                <MapPinned class="h-4 w-4" />
                                Lokasi
                            </span>
                        </a>
                        <a
                            href="#booking"
                            class="shrink-0 rounded-full bg-[color:var(--clay-surface-soft)] px-3 py-1.5 text-sm text-[color:var(--clay-body)]"
                            @click.prevent="scrollToSection('booking')"
                        >
                            <span class="inline-flex items-center gap-2">
                                <CalendarDays class="h-4 w-4" />
                                Booking
                            </span>
                        </a>
                    </div>
                </div>
            </div>

            <section id="photos" class="mt-6 scroll-mt-24">
                <div class="text-sm font-semibold">Foto</div>
                <div class="mt-3">
                    <Splide
                        v-if="galleryImages.length"
                        ref="mainGalleryRef"
                        :options="{
                            type: 'slide',
                            perPage: 1,
                            gap: '0.75rem',
                            arrows: false,
                            pagination: true,
                            drag: true,
                            speed: 450,
                        }"
                    >
                        <SplideSlide v-for="src in galleryImages" :key="src">
                            <div class="overflow-hidden rounded-2xl bg-[color:var(--clay-surface-card)]">
                                <div class="aspect-[16/10] w-full overflow-hidden">
                                    <img :src="src" alt="" class="h-full w-full object-cover" />
                                </div>
                            </div>
                        </SplideSlide>
                    </Splide>
                    <Splide
                        v-if="galleryImages.length > 1"
                        ref="thumbsGalleryRef"
                        class="mt-3 [&_.splide__slide:not(.is-active)_img]:opacity-60"
                        :options="{
                            type: 'slide',
                            rewind: false,
                            gap: '0.5rem',
                            perPage: 5,
                            pagination: false,
                            arrows: false,
                            isNavigation: true,
                            focus: 'center',
                            drag: true,
                            fixedWidth: 78,
                            fixedHeight: 52,
                            breakpoints: {
                                420: { perPage: 4, fixedWidth: 72, fixedHeight: 48 },
                            },
                        }"
                    >
                        <SplideSlide v-for="src in galleryImages" :key="`thumb-${src}`">
                            <div class="thumb h-full w-full overflow-hidden rounded-xl bg-[color:var(--clay-surface-card)]">
                                <img :src="src" alt="" class="h-full w-full object-cover" />
                            </div>
                        </SplideSlide>
                    </Splide>
                    <div v-else class="rounded-2xl bg-[color:var(--clay-surface-soft)] p-5 text-sm text-[color:var(--clay-muted)]">
                        Belum ada foto.
                    </div>
                </div>
            </section>

            <section id="map" class="mt-7 scroll-mt-24">
                <div class="text-sm font-semibold">Lokasi</div>
                <div class="mt-3 overflow-hidden rounded-2xl bg-[color:var(--clay-surface-soft)]">
                    <div ref="mapEl" class="h-64 w-full" />
                </div>
            </section>

            <section class="mt-7 border-t border-[color:var(--clay-hairline)] pt-6">
                <div class="text-sm font-semibold">Deskripsi</div>
                <div
                    v-if="property.description"
                    class="prose prose-sm mt-3 max-w-none text-[color:var(--clay-body)]"
                    v-html="property.description"
                />
                <div v-else class="mt-3 text-sm text-[color:var(--clay-muted)]">Belum ada deskripsi.</div>
            </section>

            <section
                v-if="amenitiesList.length"
                class="mt-7 border-t border-[color:var(--clay-hairline)] pt-6"
            >
                <div class="text-sm font-semibold">Fasilitas</div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span
                        v-for="(item, idx) in amenitiesList"
                        :key="idx"
                        class="rounded-full border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] px-3 py-1 text-xs text-[color:var(--clay-body)]"
                    >
                        {{ item }}
                    </span>
                </div>
            </section>

            <section
                v-if="blocked.length"
                class="mt-7 border-t border-[color:var(--clay-hairline)] pt-6"
            >
                <div class="text-sm font-semibold">Tanggal terisi</div>
                <div class="mt-3 divide-y divide-[color:var(--clay-hairline)] rounded-2xl bg-[color:var(--clay-surface-soft)]">
                    <div
                        v-for="(b, idx) in blocked"
                        :key="idx"
                        class="flex items-start justify-between gap-3 px-4 py-3 text-sm"
                    >
                        <div class="text-[color:var(--clay-body)]">
                            {{ b.check_in_date }} → {{ b.check_out_date }}
                        </div>
                        <div class="text-xs text-[color:var(--clay-muted)]">
                            {{ b.status }}
                        </div>
                    </div>
                </div>
            </section>

            <section id="booking" class="mt-7 scroll-mt-24 border-t border-[color:var(--clay-hairline)] pt-6">
                <div class="text-sm font-semibold">Booking</div>
                <div v-if="!canBook" class="mt-3 text-sm text-[color:var(--clay-muted)]">
                    Login sebagai tenant untuk membuat booking.
                </div>

                <form v-else class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <Label for="check_in_date">Check-in</Label>
                        <Input id="check_in_date" v-model="form.check_in_date" class="clay-control rounded-xl" type="date" />
                        <InputError :message="form.errors.check_in_date" />
                    </div>

                    <div>
                        <Label for="check_out_date">Check-out</Label>
                        <Input id="check_out_date" v-model="form.check_out_date" class="clay-control rounded-xl" type="date" />
                        <InputError :message="form.errors.check_out_date" />
                    </div>

                    <div>
                        <Label for="guests_count">Tamu</Label>
                        <div class="relative">
                            <Users class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[color:var(--clay-muted)]" />
                            <Input
                                id="guests_count"
                                v-model="form.guests_count"
                                class="clay-control rounded-xl pl-9"
                                type="number"
                                min="1"
                                max="30"
                            />
                        </div>
                        <InputError :message="form.errors.guests_count" />
                    </div>
                </form>
            </section>
        </div>

        <div
            class="fixed inset-x-0 bottom-0 z-50 border-t border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
        >
            <div class="mx-auto w-full max-w-md px-4 pb-[env(safe-area-inset-bottom)] pt-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold">{{ property.name }}</div>
                        <div class="mt-0.5 truncate text-xs text-[color:var(--clay-muted)]">
                            {{ property.address ?? '—' }}
                        </div>
                    </div>
                    <Button
                        v-if="canBook"
                        class="shrink-0"
                        type="button"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        Create booking
                    </Button>
                    <Button v-else variant="outline" class="shrink-0" as-child>
                        <Link href="/login">Masuk</Link>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
