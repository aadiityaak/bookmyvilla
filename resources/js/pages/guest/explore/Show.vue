<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Splide, SplideSlide } from '@splidejs/vue-splide';
import {
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Image,
    MapPinned,
    Minus,
    Plus,
    Users,
} from 'lucide-vue-next';
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
const user = computed(() => (page.props as any)?.auth?.user ?? null);
const userRole = computed(() => (user.value as any)?.role ?? null);
const isLoggedIn = computed(() => Boolean(user.value));
const canBook = computed(() => isLoggedIn.value && userRole.value === 'tenant');

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

const parseLocalDate = (iso: string) => {
    if (!iso) return null;
    const d = new Date(`${iso}T00:00:00`);
    return Number.isFinite(d.getTime()) ? d : null;
};

const toIsoDate = (d: Date) => {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
};

const isSameDay = (a: Date, b: Date) =>
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate();

const addDays = (d: Date, days: number) => {
    const next = new Date(d);
    next.setDate(next.getDate() + days);
    return next;
};

const startOfMonth = (d: Date) => new Date(d.getFullYear(), d.getMonth(), 1);
const addMonths = (d: Date, months: number) => new Date(d.getFullYear(), d.getMonth() + months, 1);

const startOfWeekMonday = (d: Date) => {
    const next = new Date(d);
    const jsDay = next.getDay(); // 0=Sun..6=Sat
    const diff = (jsDay + 6) % 7; // Mon=0..Sun=6
    next.setDate(next.getDate() - diff);
    next.setHours(0, 0, 0, 0);
    return next;
};

type CalendarCell = { date: Date; inMonth: boolean };
const getMonthGrid = (month: Date): CalendarCell[] => {
    const start = startOfWeekMonday(startOfMonth(month));
    const cells: CalendarCell[] = [];
    for (let i = 0; i < 42; i++) {
        const date = addDays(start, i);
        cells.push({
            date,
            inMonth: date.getMonth() === month.getMonth(),
        });
    }
    return cells;
};

const formatMonthTitle = (d: Date) =>
    d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });

const dayLabels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

const blockedDateSet = computed(() => {
    const set = new Set<string>();
    for (const r of props.blocked ?? []) {
        const start = parseLocalDate(r.check_in_date);
        const end = parseLocalDate(r.check_out_date);
        if (!start || !end) continue;

        const maxDays = 370;
        let cursor = new Date(start);
        cursor.setHours(0, 0, 0, 0);
        const endInclusive = new Date(end);
        endInclusive.setHours(0, 0, 0, 0);

        let guard = 0;
        while (cursor <= endInclusive && guard < maxDays) {
            set.add(toIsoDate(cursor));
            cursor = addDays(cursor, 1);
            guard++;
        }
    }
    return set;
});

const isDateBlocked = (d: Date) => blockedDateSet.value.has(toIsoDate(d));

const selectedCheckIn = computed(() => parseLocalDate(form.check_in_date));
const selectedCheckOut = computed(() => parseLocalDate(form.check_out_date));

const selectedRange = computed(() => {
    const start = selectedCheckIn.value;
    const end = selectedCheckOut.value;
    if (!start || !end) return null;
    if (end <= start) return null;
    return { start, endExclusive: end };
});

const nights = computed(() => {
    const range = selectedRange.value;
    if (!range) return 0;
    const diffMs = range.endExclusive.getTime() - range.start.getTime();
    return Math.max(0, Math.round(diffMs / 86_400_000));
});

const selectedRangeHasBlocked = computed(() => {
    const range = selectedRange.value;
    if (!range) return false;

    const maxDays = 370;
    let cursor = new Date(range.start);
    cursor.setHours(0, 0, 0, 0);
    let guard = 0;

    while (cursor < range.endExclusive && guard < maxDays) {
        if (isDateBlocked(cursor)) return true;
        cursor = addDays(cursor, 1);
        guard++;
    }

    return false;
});

const canSubmitBooking = computed(() => {
    if (!canBook.value) return false;
    if (!selectedRange.value) return false;
    if (selectedRangeHasBlocked.value) return false;
    return !form.processing;
});

const isRangeStart = (d: Date) => {
    const start = selectedCheckIn.value;
    if (!start) return false;
    return isSameDay(d, start);
};

const isRangeEnd = (d: Date) => {
    const end = selectedCheckOut.value;
    if (!end) return false;
    return isSameDay(d, end);
};

const isInBetweenRange = (d: Date) => {
    const range = selectedRange.value;
    if (!range) return false;
    if (isDateBlocked(d)) return false;
    return d > range.start && d < range.endExclusive;
};

const dayButtonClass = (d: Date, inMonth: boolean) => {
    if (!inMonth) return 'cal-day--outside';
    if (isDateBlocked(d)) return 'cal-day--blocked';
    if (isRangeStart(d) || isRangeEnd(d)) return 'cal-day--edge';
    if (isInBetweenRange(d)) return 'cal-day--inrange';
    return '';
};

const calendarMonthOffset = ref(0);
const calendarMonths = computed(() => {
    const base = startOfMonth(new Date());
    const first = addMonths(base, calendarMonthOffset.value);
    return [first, addMonths(first, 1)];
});

const calendarHint = computed(() => {
    if (!isLoggedIn.value) {
        if (!form.check_in_date) return 'Pilih tanggal untuk cek ketersediaan (lalu masuk untuk booking)';
        if (!form.check_out_date) return 'Pilih tanggal check-out (lalu masuk untuk booking)';
        if (!selectedRange.value) return 'Check-out harus setelah check-in';
        if (selectedRangeHasBlocked.value) return 'Rentang tanggal melewati tanggal yang sudah terisi';
        return nights.value > 0 ? `${nights.value} malam (masuk untuk booking)` : 'Masuk untuk booking';
    }

    if (!canBook.value) {
        const roleLabel = userRole.value ? String(userRole.value) : 'unknown';
        return `Akun kamu (${roleLabel}) tidak bisa booking. Gunakan akun tenant.`;
    }
    if (!form.check_in_date) return 'Pilih tanggal check-in';
    if (!form.check_out_date) return 'Pilih tanggal check-out';
    if (!selectedRange.value) return 'Check-out harus setelah check-in';
    if (selectedRangeHasBlocked.value) return 'Rentang tanggal melewati tanggal yang sudah terisi';
    return nights.value > 0 ? `${nights.value} malam` : 'Siap dibuat';
});

const pickFromCalendar = (d: Date) => {
    if (!Number.isFinite(d.getTime())) return;
    if (isDateBlocked(d)) return;

    const iso = toIsoDate(d);

    if (!form.check_in_date || (form.check_in_date && form.check_out_date)) {
        form.check_in_date = iso;
        form.check_out_date = '';
        return;
    }

    const checkIn = selectedCheckIn.value;
    if (!checkIn) {
        form.check_in_date = iso;
        return;
    }

    if (d <= checkIn) {
        form.check_in_date = iso;
        form.check_out_date = '';
        return;
    }

    form.check_out_date = iso;
};

const decrementGuests = () => {
    form.guests_count = Math.max(1, Number(form.guests_count ?? 1) - 1);
};

const incrementGuests = () => {
    form.guests_count = Math.min(30, Number(form.guests_count ?? 1) + 1);
};

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
    if (!canSubmitBooking.value) {
        return;
    }

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
                    class="sticky top-0 z-[1900] bg-[color:var(--clay-canvas)] px-4 py-2"
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
                        class="thumb-nav mt-3 [&_.splide__slide:not(.is-active)_img]:opacity-60"
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
                id="booking"
                class="mt-7 scroll-mt-24 border-t border-[color:var(--clay-hairline)] pt-6"
            >
                <div class="text-sm font-semibold">Booking</div>
                <div class="mt-3 rounded-2xl border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-4 shadow-xs">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-xs font-medium text-[color:var(--clay-muted)]">
                                Ketersediaan
                            </div>
                            <div class="mt-1 text-sm text-[color:var(--clay-body)]">
                                {{ calendarHint }}
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                class="h-9 w-9 rounded-full border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
                                @click="calendarMonthOffset -= 1"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </Button>
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                class="h-9 w-9 rounded-full border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
                                @click="calendarMonthOffset += 1"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-6 sm:grid-cols-2">
                        <div
                            v-for="month in calendarMonths"
                            :key="toIsoDate(month)"
                            class="min-w-0"
                        >
                            <div class="text-sm font-semibold capitalize text-center">
                                {{ formatMonthTitle(month) }}
                            </div>
                            <div class="mt-3 grid grid-cols-7 gap-1">
                                <div
                                    v-for="label in dayLabels"
                                    :key="label"
                                    class="text-center text-[10px] font-semibold tracking-wide text-[color:var(--clay-muted)]"
                                >
                                    {{ label }}
                                </div>
                            </div>
                            <div class="cal-grid mt-2 grid grid-cols-7 gap-px p-px">
                                <button
                                    v-for="cell in getMonthGrid(month)"
                                    :key="toIsoDate(cell.date)"
                                    type="button"
                                    class="cal-day relative flex h-5 w-full items-center justify-center text-[13px] tabular-nums transition"
                                    :disabled="!cell.inMonth || isDateBlocked(cell.date)"
                                    :class="dayButtonClass(cell.date, cell.inMonth)"
                                    @click="pickFromCalendar(cell.date)"
                                >
                                    {{ cell.date.getDate() }}
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <form v-if="canBook" class="mt-4" @submit.prevent="submit">
                    <div class="rounded-2xl bg-[color:var(--clay-surface-soft)] p-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <Label for="check_in_date">Check-in</Label>
                                <Input
                                    id="check_in_date"
                                    v-model="form.check_in_date"
                                    class="clay-control rounded-xl"
                                    type="date"
                                />
                                <InputError :message="form.errors.check_in_date" />
                            </div>

                            <div>
                                <Label for="check_out_date">Check-out</Label>
                                <Input
                                    id="check_out_date"
                                    v-model="form.check_out_date"
                                    class="clay-control rounded-xl"
                                    type="date"
                                />
                                <InputError :message="form.errors.check_out_date" />
                            </div>
                        </div>

                        <div class="mt-4">
                            <Label for="guests_count">Tamu</Label>
                            <div class="mt-1 flex items-stretch gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="h-10 w-10 rounded-xl"
                                    @click="decrementGuests"
                                >
                                    <Minus class="h-4 w-4" />
                                </Button>
                                <div class="relative min-w-0 flex-1">
                                    <Users class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[color:var(--clay-muted)]" />
                                    <Input
                                        id="guests_count"
                                        v-model="form.guests_count"
                                        class="clay-control rounded-xl pl-9 text-center"
                                        type="number"
                                        min="1"
                                        max="30"
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="h-10 w-10 rounded-xl"
                                    @click="incrementGuests"
                                >
                                    <Plus class="h-4 w-4" />
                                </Button>
                            </div>
                            <InputError :message="form.errors.guests_count" />
                        </div>

                        <div
                            v-if="form.check_in_date && form.check_out_date && !selectedRange"
                            class="mt-3 text-xs text-[color:var(--clay-muted)]"
                        >
                            Check-out harus setelah check-in.
                        </div>
                        <div
                            v-else-if="selectedRangeHasBlocked"
                            class="mt-3 text-xs text-[color:var(--clay-muted)]"
                        >
                            Rentang tanggal melewati tanggal yang sudah terisi.
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3">
                            <div class="text-xs text-[color:var(--clay-muted)]">
                                <span v-if="nights > 0">{{ nights }} malam</span>
                                <span v-else>Pilih tanggal dulu</span>
                            </div>
                            <Button
                                class="shrink-0 rounded-xl"
                                type="submit"
                                :disabled="!canSubmitBooking"
                            >
                                Buat booking
                            </Button>
                        </div>
                    </div>
                </form>
                <div
                    v-else-if="isLoggedIn"
                    class="mt-4 rounded-2xl bg-[color:var(--clay-surface-soft)] p-4 text-sm text-[color:var(--clay-muted)]"
                >
                    Akun kamu tidak punya akses untuk booking. Gunakan akun dengan role tenant.
                </div>
                <div
                    v-else
                    class="mt-4 rounded-2xl bg-[color:var(--clay-surface-soft)] p-4 text-sm text-[color:var(--clay-muted)]"
                >
                    Masuk sebagai tenant untuk membuat booking.
                </div>
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
                        :disabled="!canSubmitBooking"
                        @click="submit"
                    >
                        Buat booking
                    </Button>
                    <Button v-else-if="!isLoggedIn" variant="outline" class="shrink-0" as-child>
                        <Link href="/login">Masuk</Link>
                    </Button>
                    <Button v-else variant="outline" class="shrink-0" disabled>
                        Tidak bisa booking
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.cal-grid {
    border-radius: 1rem;
}

.cal-day {
    border-radius: 0.875rem;
    background: var(--clay-canvas);
    color: var(--clay-ink);
}

.cal-day:not(:disabled):hover {
    background: var(--clay-surface-soft);
    color: var(--primary);
}

.cal-day:disabled {
    cursor: not-allowed;
}

.cal-day--outside {
    color: var(--clay-muted);
    opacity: 0.35;
}

.cal-day--blocked {
    background: var(--clay-surface-soft);
    color: var(--clay-muted);
    text-decoration: line-through;
}

.cal-day--inrange {
    background: color-mix(in srgb, var(--primary) 70%, var(--clay-canvas));
    color: color-mix(in srgb, var(--primary) 10%, var(--clay-canvas));
}

.cal-day--edge {
    background: var(--primary);
    color: var(--primary-foreground);
    font-weight: 600;
    position: relative;
    z-index: 1;
}

@media (prefers-reduced-motion: reduce) {
    .cal-day {
        transition: none;
    }
}
</style>
