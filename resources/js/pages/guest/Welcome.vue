<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

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

const villaTrackRef = ref<HTMLElement | null>(null);
const kostTrackRef = ref<HTMLElement | null>(null);

const scrollCarousel = (el: HTMLElement | null, direction: 'prev' | 'next') => {
    if (!el) return;
    const amount = Math.round(el.clientWidth * 0.85);
    el.scrollBy({
        left: direction === 'next' ? amount : -amount,
        behavior: 'smooth',
    });
};

type DragState = {
    active: boolean;
    startX: number;
    startScrollLeft: number;
    pointerId: number | null;
};

type MouseDragState = Omit<DragState, 'pointerId'> & { el: HTMLElement | null };

const lastDragAt = ref(0);
const markDragged = () => {
    lastDragAt.value = Date.now();
};
const wasJustDragged = () => Date.now() - lastDragAt.value < 250;

const startMouseDrag = (e: MouseEvent, el: HTMLElement | null) => {
    if (!el) return;
    if (e.button !== 0) return;
    if (e.target instanceof Element && e.target.closest('a,button,input,select,textarea,label')) {
        return;
    }

    const state: MouseDragState = {
        active: true,
        startX: e.clientX,
        startScrollLeft: el.scrollLeft,
        el,
    };

    const onMove = (ev: MouseEvent) => {
        if (!state.active || !state.el) return;
        const dx = ev.clientX - state.startX;
        state.el.scrollLeft = state.startScrollLeft - dx;
        if (Math.abs(dx) > 8) {
            markDragged();
        }
    };

    const onUp = () => {
        state.active = false;
        markDragged();
        window.removeEventListener('mousemove', onMove);
        window.removeEventListener('mouseup', onUp);
    };

    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp);
};

const onCarouselWheel = (e: WheelEvent) => {
    const el = e.currentTarget as HTMLElement | null;
    if (!el) return;

    if (el.scrollWidth <= el.clientWidth) {
        return;
    }

    const isHorizontalIntent = Math.abs(e.deltaX) > Math.abs(e.deltaY);
    const allowShiftWheel = e.shiftKey && Math.abs(e.deltaY) > 0;

    if (!isHorizontalIntent && !allowShiftWheel) {
        return;
    }

    const delta = isHorizontalIntent ? e.deltaX : e.deltaY;
    e.preventDefault();
    el.scrollLeft += delta;
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
                                                @click="scrollCarousel(villaTrackRef, 'prev')"
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
                                                @click="scrollCarousel(villaTrackRef, 'next')"
                                            >
                                                <ChevronRight class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    ref="villaTrackRef"
                                    class="mt-4 flex snap-x snap-mandatory gap-3 overflow-x-auto pb-1 pr-4 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden select-none cursor-grab active:cursor-grabbing"
                                    @wheel="onCarouselWheel"
                                    @mousedown="startMouseDrag($event, villaTrackRef)"
                                >
                                    <div
                                        v-for="s in villaCarouselItems"
                                        :key="s.key"
                                        class="w-[calc(100%-2rem)] snap-start shrink-0"
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
                                                    class="mt-1 line-clamp-2 text-xs text-[color:var(--clay-muted)]"
                                                >
                                                    {{ s.subtitle }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                                                @click="scrollCarousel(kostTrackRef, 'prev')"
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
                                                @click="scrollCarousel(kostTrackRef, 'next')"
                                            >
                                                <ChevronRight class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    ref="kostTrackRef"
                                    class="mt-4 flex snap-x snap-mandatory gap-3 overflow-x-auto pb-1 pr-4 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden select-none cursor-grab active:cursor-grabbing"
                                    @wheel="onCarouselWheel"
                                    @mousedown="startMouseDrag($event, kostTrackRef)"
                                >
                                    <div
                                        v-for="s in kostCarouselItems"
                                        :key="s.key"
                                        class="w-[calc(100%-2rem)] snap-start shrink-0"
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
                                                    class="mt-1 line-clamp-2 text-xs text-[color:var(--clay-muted)]"
                                                >
                                                    {{ s.subtitle }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
