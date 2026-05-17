<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Splide, SplideSlide } from '@splidejs/vue-splide';
import { ArrowUpRight, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';
import '@splidejs/vue-splide/css';

const props = withDefaults(
    defineProps<{
        canRegister: boolean;
        villas: {
            id: number;
            type: 'villa';
            name: string;
            featured_image: string | null;
            address: string | null;
        }[];
        kosts: {
            id: number;
            type: 'kost';
            name: string;
            featured_image: string | null;
            address: string | null;
        }[];
    }>(),
    {
        canRegister: true,
        villas: () => [],
        kosts: () => [],
    },
);

const page = usePage();
const user = computed(() => (page.props as any)?.auth?.user ?? null);
const role = computed(() => user.value?.role ?? null);

const primaryCtaHref = computed(() => {
    if (!user.value) return '/explore';
    if (role.value === 'tenant') return '/bookings';
    if (role.value === 'host' || role.value === 'investor' || role.value === 'admin')
        return '/properties';
    return dashboard();
});

const primaryCtaLabel = computed(() => {
    if (!user.value) return 'Explore Villa';
    if (role.value === 'tenant') return 'Booking Saya';
    if (role.value === 'host' || role.value === 'investor' || role.value === 'admin')
        return 'Kelola Property';
    return 'Dashboard';
});

type CarouselItem = {
    key: string;
    badge: 'Villa' | 'Kost';
    title: string;
    subtitle: string;
    imageUrl: string | null;
    href: string | null;
    bg: string;
};

const toImageUrl = (path: string | null) => {
    if (!path) return null;
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    return `/storage/${path}`;
};

const villaCarouselItems = computed<CarouselItem[]>(() => {
    if (props.villas.length) {
        return props.villas.map((p) => ({
            key: `villa-${p.id}`,
            badge: 'Villa',
            title: p.name,
            subtitle: p.address ?? '—',
            imageUrl: toImageUrl(p.featured_image),
            href: `/explore/${p.id}`,
            bg: 'var(--clay-brand-teal)',
        }));
    }

    return [
        {
            key: 'villa-fallback-1',
            badge: 'Villa',
            title: 'Villa Family',
            subtitle: 'Cocok untuk liburan bareng',
            imageUrl: null,
            href: '/explore',
            bg: 'var(--clay-brand-teal)',
        },
        {
            key: 'villa-fallback-2',
            badge: 'Villa',
            title: 'Villa Private',
            subtitle: 'Tenang, nyaman, dan eksklusif',
            imageUrl: null,
            href: '/explore',
            bg: 'var(--clay-brand-pink)',
        },
        {
            key: 'villa-fallback-3',
            badge: 'Villa',
            title: 'Villa View',
            subtitle: 'Pilihan dengan spot terbaik',
            imageUrl: null,
            href: '/explore',
            bg: 'var(--clay-brand-lavender)',
        },
    ];
});

const kostCarouselItems = computed<CarouselItem[]>(() => {
    if (props.kosts.length) {
        return props.kosts.map((p) => ({
            key: `kost-${p.id}`,
            badge: 'Kost',
            title: p.name,
            subtitle: p.address ?? '—',
            imageUrl: toImageUrl(p.featured_image),
            href: null,
            bg: 'var(--clay-brand-ochre)',
        }));
    }

    return [
        {
            key: 'kost-fallback-1',
            badge: 'Kost',
            title: 'Kost Harian',
            subtitle: 'Fleksibel untuk short stay',
            imageUrl: null,
            href: null,
            bg: 'var(--clay-brand-ochre)',
        },
        {
            key: 'kost-fallback-2',
            badge: 'Kost',
            title: 'Kost Bulanan',
            subtitle: 'Nyaman untuk tinggal lebih lama',
            imageUrl: null,
            href: null,
            bg: 'var(--clay-brand-peach)',
        },
        {
            key: 'kost-fallback-3',
            badge: 'Kost',
            title: 'Kost Premium',
            subtitle: 'Fasilitas lengkap & rapi',
            imageUrl: null,
            href: null,
            bg: 'var(--clay-surface-strong)',
        },
    ];
});

const villaSplideRef = ref<any>(null);
const kostSplideRef = ref<any>(null);

const lastDragAt = ref(0);
const markDragged = () => {
    lastDragAt.value = Date.now();
};
const wasJustDragged = () => Date.now() - lastDragAt.value < 250;

const goCarousel = (refEl: any, direction: 'prev' | 'next') => {
    const api = refEl?.value?.splide ?? refEl?.value;
    const control = direction === 'next' ? '>' : '<';
    api?.go?.(control);
};

const onCarouselItemClick = (href: string | null, e: MouseEvent) => {
    if (!href) return;
    if (wasJustDragged()) return;
    if (e.target instanceof Element && e.target.closest('a,button,input,select,textarea,label')) {
        return;
    }
    router.visit(href);
};
</script>

<template>
    <Head title="BookMyVilla" />

    <div class="bg-[color:var(--clay-canvas)] text-[color:var(--clay-ink)]">
        <section class="mx-auto w-full max-w-md px-6 py-12">
                <div class="grid grid-cols-1 items-start gap-10">
                    <div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-soft)] px-3 py-1 text-xs text-[color:var(--clay-muted)]"
                        >
                            <span class="h-2 w-2 rounded-full bg-[color:var(--clay-brand-ochre)]" />
                            Booking villa harian
                        </div>

                        <h1
                            class="mt-5 text-4xl font-semibold leading-tight tracking-tight text-[color:var(--clay-ink)]"
                        >
                            Temukan villa impianmu. Booking harian, tanpa ribet.
                        </h1>
                        <p class="mt-4 max-w-xl text-base text-[color:var(--clay-body)]">
                            Jelajahi villa yang tersedia, pilih tanggal, dan buat booking.
                            Untuk host, kelola listing, foto, dan status publish dalam satu tempat.
                        </p>

                        <div class="mt-7 flex flex-wrap items-center gap-3">
                            <Button as-child>
                                <Link :href="primaryCtaHref">{{ primaryCtaLabel }}</Link>
                            </Button>
                            <Button variant="outline" as-child>
                                <Link href="/explore">Lihat Semua Villa</Link>
                            </Button>
                        </div>

                        <div class="mt-8 flex flex-col gap-6">
                            <div
                                class="mx-auto w-full max-w-md rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="text-sm font-medium text-[color:var(--clay-ink)]"
                                        >
                                            Rekomendasi Villa
                                        </div>
                                        <div
                                            class="mt-1 text-xs text-[color:var(--clay-muted)]"
                                        >
                                            Swipe untuk lihat pilihan
                                        </div>
                                    </div>
                                    <div
                                        class="shrink-0 overflow-hidden rounded-full border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
                                    >
                                        <div class="flex items-center">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                class="h-8 w-8 rounded-none"
                                                aria-label="Sebelumnya"
                                                @click="goCarousel(villaSplideRef, 'prev')"
                                            >
                                                <ChevronLeft class="h-4 w-4" />
                                            </Button>
                                            <div class="h-8 w-px bg-[color:var(--clay-hairline)]" />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                class="h-8 w-8 rounded-none"
                                                aria-label="Berikutnya"
                                                @click="goCarousel(villaSplideRef, 'next')"
                                            >
                                                <ChevronRight class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <Splide
                                    ref="villaSplideRef"
                                    class="mt-4"
                                    :options="{
                                        type: 'slide',
                                        perPage: 1,
                                        gap: '0.75rem',
                                        padding: { right: '2.5rem' },
                                        focus: 0,
                                        arrows: false,
                                        pagination: false,
                                        drag: true,
                                        speed: 450,
                                    }"
                                    @moved="markDragged"
                                >
                                    <SplideSlide
                                        v-for="s in villaCarouselItems"
                                        :key="s.key"
                                        class="pb-1"
                                    >
                                        <div
                                            class="relative w-full overflow-hidden rounded-xl border border-[color:var(--clay-hairline)]"
                                            :class="s.href ? 'cursor-pointer' : ''"
                                            @click="onCarouselItemClick(s.href, $event)"
                                        >
                                            <div
                                                class="relative aspect-[16/9] sm:aspect-[4/3]"
                                                :style="{ backgroundColor: s.bg }"
                                            >
                                                <img
                                                    v-if="s.imageUrl"
                                                    :src="s.imageUrl"
                                                    alt=""
                                                    class="absolute inset-0 h-full w-full object-cover"
                                                />
                                                <div
                                                    class="absolute inset-0 bg-gradient-to-t from-black/25 to-transparent"
                                                />
                                                <div
                                                    class="absolute left-3 top-3 rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-medium text-white"
                                                >
                                                    {{ s.badge }}
                                                </div>
                                                <Link
                                                    v-if="s.href"
                                                    :href="s.href"
                                                    class="absolute right-3 top-3 inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/25 bg-white/10 text-white backdrop-blur-sm"
                                                >
                                                    <ArrowUpRight class="h-4 w-4" />
                                                </Link>
                                                <div
                                                    v-else
                                                    class="absolute right-3 top-3 inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/25 bg-white/10 text-white/70 backdrop-blur-sm"
                                                >
                                                    <ArrowUpRight class="h-4 w-4" />
                                                </div>
                                            </div>
                                            <div
                                                class="bg-[color:var(--clay-canvas)] p-3"
                                            >
                                                <div
                                                    class="text-sm font-semibold text-[color:var(--clay-ink)]"
                                                >
                                                    {{ s.title }}
                                                </div>
                                                <div
                                                    class="mt-1 truncate text-xs text-[color:var(--clay-muted)]"
                                                >
                                                    {{ s.subtitle }}
                                                </div>
                                            </div>
                                        </div>
                                    </SplideSlide>
                                </Splide>
                            </div>

                            <div
                                class="mx-auto w-full max-w-md rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="text-sm font-medium text-[color:var(--clay-ink)]"
                                        >
                                            Rekomendasi Kost
                                        </div>
                                        <div
                                            class="mt-1 text-xs text-[color:var(--clay-muted)]"
                                        >
                                            Swipe untuk lihat pilihan
                                        </div>
                                    </div>
                                    <div
                                        class="shrink-0 overflow-hidden rounded-full border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
                                    >
                                        <div class="flex items-center">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                class="h-8 w-8 rounded-none"
                                                aria-label="Sebelumnya"
                                                @click="goCarousel(kostSplideRef, 'prev')"
                                            >
                                                <ChevronLeft class="h-4 w-4" />
                                            </Button>
                                            <div class="h-8 w-px bg-[color:var(--clay-hairline)]" />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                class="h-8 w-8 rounded-none"
                                                aria-label="Berikutnya"
                                                @click="goCarousel(kostSplideRef, 'next')"
                                            >
                                                <ChevronRight class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <Splide
                                    ref="kostSplideRef"
                                    class="mt-4"
                                    :options="{
                                        type: 'slide',
                                        perPage: 1,
                                        gap: '0.75rem',
                                        padding: { right: '2.5rem' },
                                        focus: 0,
                                        arrows: false,
                                        pagination: false,
                                        drag: true,
                                        speed: 450,
                                    }"
                                    @moved="markDragged"
                                >
                                    <SplideSlide
                                        v-for="s in kostCarouselItems"
                                        :key="s.key"
                                        class="pb-1"
                                    >
                                        <div
                                            class="relative w-full overflow-hidden rounded-xl border border-[color:var(--clay-hairline)]"
                                        >
                                            <div
                                                class="relative aspect-[16/9] sm:aspect-[4/3]"
                                                :style="{ backgroundColor: s.bg }"
                                            >
                                                <img
                                                    v-if="s.imageUrl"
                                                    :src="s.imageUrl"
                                                    alt=""
                                                    class="absolute inset-0 h-full w-full object-cover"
                                                />
                                                <div
                                                    class="absolute inset-0 bg-gradient-to-t from-black/10 to-transparent"
                                                />
                                                <div
                                                    class="absolute left-3 top-3 rounded-full bg-black/10 px-2.5 py-1 text-[11px] font-medium text-[color:var(--clay-ink)]"
                                                >
                                                    {{ s.badge }}
                                                </div>
                                                <div
                                                    class="absolute right-3 top-3 inline-flex h-9 w-9 items-center justify-center rounded-full border border-black/10 bg-black/5 text-[color:var(--clay-ink)] backdrop-blur-sm"
                                                >
                                                    <ArrowUpRight class="h-4 w-4 opacity-70" />
                                                </div>
                                            </div>
                                            <div
                                                class="bg-[color:var(--clay-canvas)] p-3"
                                            >
                                                <div
                                                    class="text-sm font-semibold text-[color:var(--clay-ink)]"
                                                >
                                                    {{ s.title }}
                                                </div>
                                                <div
                                                    class="mt-1 truncate text-xs text-[color:var(--clay-muted)]"
                                                >
                                                    {{ s.subtitle }}
                                                </div>
                                            </div>
                                        </div>
                                    </SplideSlide>
                                </Splide>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div
                            class="overflow-hidden rounded-xl border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-soft)] p-5"
                        >
                            <div class="grid grid-cols-1 gap-4">
                                <div class="rounded-xl bg-[color:var(--clay-brand-pink)] p-5 text-white">
                                    <div class="text-sm font-medium">Explore</div>
                                    <div class="mt-2 text-sm text-white/90">
                                        Cari villa berdasarkan nama atau lokasi.
                                    </div>
                                    <div class="mt-4 rounded-lg bg-white/10 p-3 text-xs">
                                        /explore → listing villa publish
                                    </div>
                                </div>
                                <div class="rounded-xl bg-[color:var(--clay-brand-teal)] p-5 text-white">
                                    <div class="text-sm font-medium">Booking</div>
                                    <div class="mt-2 text-sm text-white/90">
                                        Tentukan tanggal check-in & check-out, lalu submit booking.
                                    </div>
                                    <div class="mt-4 rounded-lg bg-white/10 p-3 text-xs">
                                        Status: pending_payment → confirmed
                                    </div>
                                </div>
                                <div
                                    class="rounded-xl bg-[color:var(--clay-brand-lavender)] p-5 text-[color:var(--clay-ink)]"
                                >
                                    <div class="text-sm font-medium">Detail Property</div>
                                    <div class="mt-2 text-sm text-[color:var(--clay-body)]">
                                        Galeri, deskripsi WYSIWYG, dan highlight fasilitas.
                                    </div>
                                    <div
                                        class="mt-4 rounded-lg bg-[color:var(--clay-canvas)] p-3 text-xs text-[color:var(--clay-muted)]"
                                    >
                                        Preview foto + deskripsi
                                    </div>
                                </div>
                                <div
                                    class="rounded-xl bg-[color:var(--clay-brand-peach)] p-5 text-[color:var(--clay-ink)]"
                                >
                                    <div class="text-sm font-medium">Untuk Host</div>
                                    <div class="mt-2 text-sm text-[color:var(--clay-body)]">
                                        Kelola property, publish/unpublish, dan update foto.
                                    </div>
                                    <div
                                        class="mt-4 rounded-lg bg-[color:var(--clay-canvas)] p-3 text-xs text-[color:var(--clay-muted)]"
                                    >
                                        /properties → dashboard host
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-6 rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-5"
                        >
                            <div class="text-sm font-medium text-[color:var(--clay-ink)]">
                                Mulai dari mana?
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-3">
                                <div
                                    class="rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4"
                                >
                                    <div class="text-sm font-medium">Mau sewa villa</div>
                                    <div class="mt-1 text-sm text-[color:var(--clay-body)]">
                                        Browse dulu, lalu login untuk booking.
                                    </div>
                                    <div class="mt-4 flex gap-2">
                                        <Button size="sm" as-child>
                                            <Link href="/explore">Explore</Link>
                                        </Button>
                                        <Button
                                            v-if="!$page.props.auth.user"
                                            size="sm"
                                            variant="outline"
                                            as-child
                                        >
                                            <Link :href="login()">Masuk</Link>
                                        </Button>
                                    </div>
                                </div>
                                <div
                                    class="rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4"
                                >
                                    <div class="text-sm font-medium">Mau jadi host</div>
                                    <div class="mt-1 text-sm text-[color:var(--clay-body)]">
                                        Login sebagai host/admin untuk mengelola property.
                                    </div>
                                    <div class="mt-4 flex gap-2">
                                        <Button size="sm" as-child>
                                            <Link href="/properties">Kelola Property</Link>
                                        </Button>
                                        <Button
                                            v-if="!$page.props.auth.user && canRegister"
                                            size="sm"
                                            variant="outline"
                                            as-child
                                        >
                                            <Link :href="register()">Daftar</Link>
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </section>

    </div>
</template>
