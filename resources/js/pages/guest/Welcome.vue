<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Splide, SplideSlide } from '@splidejs/vue-splide';
import { ArrowUpRight, Building2, CalendarDays, ChevronLeft, ChevronRight, MapPinned, Search } from 'lucide-vue-next';
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
        articles: {
            id: number;
            title: string;
            slug: string;
            featured_image: string | null;
            excerpt: string | null;
            published_at: string | null;
        }[];
    }>(),
    {
        canRegister: true,
        villas: () => [],
        kosts: () => [],
        articles: () => [],
    },
);

const page = usePage();
const branding = computed(() => (page.props as any)?.branding ?? null);
const appName = computed(() => branding.value?.app_name ?? (page.props as any)?.name ?? 'BookMyVilla');
const tagline = computed(() => branding.value?.tagline ?? 'Booking villa harian, tanpa ribet.');
const user = computed(() => (page.props as any)?.auth?.user ?? null);
const role = computed(() => user.value?.role ?? null);
const greetingName = computed(() => user.value?.name ?? appName.value);

const primaryCtaHref = computed(() => {
    if (!user.value) {
        return '/explore';
    }

    if (role.value === 'tenant') {
        return '/bookings';
    }

    if (role.value === 'host' || role.value === 'investor' || role.value === 'admin') {
        return '/properties';
    }

    return dashboard();
});

const primaryCtaLabel = computed(() => {
    if (!user.value) {
        return 'Explore Villa';
    }

    if (role.value === 'tenant') {
        return 'Booking Saya';
    }

    if (role.value === 'host' || role.value === 'investor' || role.value === 'admin') {
        return 'Kelola Property';
    }

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
    if (!path) {
        return null;
    }

    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    return `/storage/${path}`;
};

const formatDate = (iso: string | null) => {
    if (!iso) return '';
    const d = new Date(iso);
    if (!Number.isFinite(d.getTime())) return '';
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
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
    const api = refEl?.splide ?? refEl?.value?.splide ?? refEl?.value ?? refEl;
    const control = direction === 'next' ? '>' : '<';
    api?.go?.(control);
};

const onCarouselItemClick = (href: string | null, e: MouseEvent) => {
    if (!href) {
        return;
    }

    if (wasJustDragged()) {
        return;
    }

    if (e.target instanceof Element && e.target.closest('a,button,input,select,textarea,label')) {
        return;
    }

    router.visit(href);
};

type QuickLink = {
    title: string;
    subtitle: string;
    href: string;
    icon: any;
};

const quickLinks = computed<QuickLink[]>(() => [
    { title: 'Explore', subtitle: 'Cari villa', href: '/explore', icon: Search },
    { title: 'Area', subtitle: 'Lokasi populer', href: '/explore', icon: MapPinned },
    { title: 'Booking', subtitle: 'Cek pesanan', href: user.value ? '/my-property' : '/login', icon: CalendarDays },
    { title: 'Host', subtitle: 'Kelola listing', href: user.value ? '/properties' : '/login', icon: Building2 },
]);
</script>

<template>
    <Head title="BookMyVilla" />

    <div class="min-h-dvh bg-[color:var(--clay-canvas)] text-[color:var(--clay-ink)]">
        <section class="mx-auto w-full max-w-md px-4 pb-[calc(7rem+env(safe-area-inset-bottom))] pt-6">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="text-xs font-medium text-[color:var(--clay-muted)]">Hello</div>
                    <div class="mt-1 truncate text-lg font-semibold tracking-tight">
                        {{ greetingName }}
                    </div>
                    <div class="mt-1 text-sm text-[color:var(--clay-body)]">
                        {{ tagline }}
                    </div>
                </div>
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)]"
                    aria-hidden="true"
                >
                    <span class="h-2.5 w-2.5 rounded-full bg-[color:var(--primary)]" />
                </div>
            </div>

            <div class="mt-5">
                <Button
                    variant="outline"
                    as-child
                    class="h-11 w-full justify-start gap-2 rounded-xl bg-[color:var(--clay-canvas)]"
                >
                    <Link href="/explore" class="w-full">
                        <Search class="h-4 w-4 text-[color:var(--clay-muted)]" />
                        <span class="text-[color:var(--clay-muted)]">Cari villa, area, atau lokasi…</span>
                    </Link>
                </Button>
            </div>

            <div class="mt-5 grid grid-cols-4 gap-3">
                <Link
                    v-for="item in quickLinks"
                    :key="item.title"
                    :href="item.href"
                    class="group flex flex-col items-center gap-2 rounded-2xl border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] px-2 py-3 text-center transition-shadow hover:shadow-sm"
                >
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[color:var(--clay-canvas)]"
                    >
                        <component :is="item.icon" class="h-5 w-5 text-[color:var(--primary)]" />
                    </span>
                    <span class="leading-tight">
                        <span class="block text-xs font-medium">{{ item.title }}</span>
                        <span class="block text-[11px] text-[color:var(--clay-muted)]">
                            {{ item.subtitle }}
                        </span>
                    </span>
                </Link>
            </div>

            <div
                class="relative mt-6 overflow-hidden rounded-2xl border border-[color:var(--clay-hairline)] bg-[linear-gradient(135deg,var(--clay-surface-soft),var(--clay-canvas))]"
            >
                <div
                    class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-[color:var(--primary)] opacity-10 blur-xl"
                />
                <div
                    class="pointer-events-none absolute -bottom-12 -left-12 h-40 w-40 rounded-full bg-[color:var(--clay-brand-pink)] opacity-10 blur-xl"
                />
                <div class="relative p-4">
                    <div class="text-xs font-medium text-[color:var(--clay-muted)]">
                        Highlight
                    </div>
                    <div class="mt-1 text-base font-semibold tracking-tight">
                        Booking villa harian, cepat & simpel
                    </div>
                    <div class="mt-1 text-sm text-[color:var(--clay-body)]">
                        Lihat listing terbaru, pilih tanggal, lalu booking.
                    </div>
                    <div class="mt-4 flex gap-2">
                        <Button size="sm" as-child>
                            <Link :href="primaryCtaHref">{{ primaryCtaLabel }}</Link>
                        </Button>
                        <Button size="sm" variant="outline" as-child>
                            <Link href="/explore">Lihat Semua</Link>
                        </Button>
                    </div>
                </div>
            </div>

            <div class="mt-7">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold">Featured Villas</div>
                        <div class="mt-0.5 text-xs text-[color:var(--clay-muted)]">
                            Swipe untuk lihat pilihan
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button
                            type="button"
                            size="icon-sm"
                            variant="outline"
                            aria-label="Sebelumnya"
                            class="rounded-full"
                            @click="goCarousel(villaSplideRef, 'prev')"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        <Button
                            type="button"
                            size="icon-sm"
                            variant="outline"
                            aria-label="Berikutnya"
                            class="rounded-full"
                            @click="goCarousel(villaSplideRef, 'next')"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <Splide
                    ref="villaSplideRef"
                    class="mt-4"
                    :options="{
                        type: 'slide',
                        perPage: 1,
                        gap: '0.9rem',
                        padding: { right: '2.75rem' },
                        focus: 0,
                        arrows: false,
                        pagination: false,
                        drag: true,
                        speed: 450,
                    }"
                    @moved="markDragged"
                >
                    <SplideSlide v-for="s in villaCarouselItems" :key="s.key" class="pb-1">
                        <div
                            class="relative w-full overflow-hidden rounded-2xl border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] shadow-xs transition-shadow"
                            :class="s.href ? 'cursor-pointer hover:shadow-sm' : ''"
                            @click="onCarouselItemClick(s.href, $event)"
                        >
                            <div class="relative aspect-[16/10]" :style="{ backgroundColor: s.bg }">
                                <img
                                    v-if="s.imageUrl"
                                    :src="s.imageUrl"
                                    alt=""
                                    class="absolute inset-0 h-full w-full object-cover"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/25 to-transparent" />
                                <div
                                    class="absolute left-3 top-3 rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-medium text-white backdrop-blur-sm"
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
                            <div class="p-3">
                                <div class="text-sm font-semibold">{{ s.title }}</div>
                                <div class="mt-1 truncate text-xs text-[color:var(--clay-muted)]">
                                    {{ s.subtitle }}
                                </div>
                            </div>
                        </div>
                    </SplideSlide>
                </Splide>
            </div>

            <div class="mt-7">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold">Rekomendasi Kost</div>
                        <div class="mt-0.5 text-xs text-[color:var(--clay-muted)]">
                            Swipe untuk lihat pilihan
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button
                            type="button"
                            size="icon-sm"
                            variant="outline"
                            aria-label="Sebelumnya"
                            class="rounded-full"
                            @click="goCarousel(kostSplideRef, 'prev')"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        <Button
                            type="button"
                            size="icon-sm"
                            variant="outline"
                            aria-label="Berikutnya"
                            class="rounded-full"
                            @click="goCarousel(kostSplideRef, 'next')"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <Splide
                    ref="kostSplideRef"
                    class="mt-4"
                    :options="{
                        type: 'slide',
                        perPage: 1,
                        gap: '0.9rem',
                        padding: { right: '2.75rem' },
                        focus: 0,
                        arrows: false,
                        pagination: false,
                        drag: true,
                        speed: 450,
                    }"
                    @moved="markDragged"
                >
                    <SplideSlide v-for="s in kostCarouselItems" :key="s.key" class="pb-1">
                        <div
                            class="relative w-full overflow-hidden rounded-2xl border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] shadow-xs"
                        >
                            <div class="relative aspect-[16/10]" :style="{ backgroundColor: s.bg }">
                                <img
                                    v-if="s.imageUrl"
                                    :src="s.imageUrl"
                                    alt=""
                                    class="absolute inset-0 h-full w-full object-cover"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/10 to-transparent" />
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
                            <div class="p-3">
                                <div class="text-sm font-semibold">{{ s.title }}</div>
                                <div class="mt-1 truncate text-xs text-[color:var(--clay-muted)]">
                                    {{ s.subtitle }}
                                </div>
                            </div>
                        </div>
                    </SplideSlide>
                </Splide>
            </div>

            <div class="mt-8">
                <div class="text-xs font-medium tracking-wide text-[color:var(--clay-muted)]">
                    Your Reading List
                </div>
                <div class="mt-1 text-base font-semibold">
                    Artikel terbaru
                </div>

                <div class="mt-3">
                    <div v-if="props.articles.length" class="divide-y divide-[color:var(--clay-hairline)]">
                        <Link
                            v-for="article in props.articles.slice(0, 5)"
                            :key="article.id"
                            :href="`/articles/${article.slug}`"
                            class="group flex items-start gap-3 py-3"
                        >
                            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-md bg-[color:var(--clay-surface-soft)]">
                                <img
                                    v-if="toImageUrl(article.featured_image)"
                                    :src="toImageUrl(article.featured_image) ?? ''"
                                    alt=""
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="line-clamp-2 text-sm font-semibold text-[color:var(--clay-ink)]">
                                    {{ article.title }}
                                </div>
                                <div class="mt-1 flex items-center gap-2 text-[11px] text-[color:var(--clay-muted)]">
                                    <span class="inline-flex h-4 items-center rounded bg-[color:var(--clay-surface-soft)] px-1.5">
                                        Artikel
                                    </span>
                                    <span>{{ formatDate(article.published_at) }}</span>
                                </div>
                                <div v-if="article.excerpt" class="mt-1 line-clamp-2 text-xs text-[color:var(--clay-body)]">
                                    {{ article.excerpt }}
                                </div>
                            </div>

                            <ChevronRight class="mt-1 h-4 w-4 shrink-0 text-[color:var(--clay-muted)] opacity-70 group-hover:opacity-100" />
                        </Link>
                    </div>

                    <div v-else class="py-3 text-sm text-[color:var(--clay-muted)]">
                        Belum ada artikel.
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
