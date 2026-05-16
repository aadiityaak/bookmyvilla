<script setup lang="ts">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Building2, Home, LogIn, Search, UserRound } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { toUrl } from '@/lib/utils';
import { dashboard, login, register } from '@/routes';

const props = withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
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

type BottomNavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: any;
    exact?: boolean;
};

const showBottomNav = computed(() => true);

const bottomNavItems = computed<BottomNavItem[]>(() => {
    const items: BottomNavItem[] = [
        { title: 'Home', href: '/', icon: Home, exact: true },
        { title: 'Explore', href: '/explore', icon: Search },
    ];

    if (!user.value) {
        items.push({ title: 'My Property', href: login(), icon: Building2 });
        items.push({ title: 'Masuk', href: login(), icon: LogIn });
        return items;
    }

    if (role.value === 'tenant') {
        items.push({ title: 'My Property', href: '/my-property', icon: Building2 });
        items.push({ title: 'Akun', href: dashboard(), icon: UserRound });
        return items;
    }

    items.push({ title: 'Dashboard', href: dashboard(), icon: UserRound });
    return items;
});

const isActive = (item: BottomNavItem) => {
    const url = page.url;
    const href = toUrl(item.href);
    if (item.exact) return url === href;
    if (href === '/explore') return url.startsWith('/explore');
    if (href === '/bookings') return url.startsWith('/bookings');
    if (href === '/my-property') return url.startsWith('/my-property') || url.startsWith('/bookings');
    return url.startsWith(href);
};
</script>

<template>
    <Head title="BookMyVilla" />

    <div
        class="min-h-screen bg-[color:var(--clay-canvas)] text-[color:var(--clay-ink)]"
        :class="[
            showBottomNav ? 'pb-[calc(5.25rem+env(safe-area-inset-bottom))]' : '',
        ]"
    >
        <header class="border-b border-[color:var(--clay-hairline)]">
            <div class="mx-auto flex h-16 w-full max-w-md items-center justify-between px-6">
                <Link href="/" class="flex items-center gap-2">
                    <AppLogo />
                </Link>

                <nav class="flex items-center gap-2">
                    <Button variant="ghost" as-child>
                        <Link href="/explore">Explore</Link>
                    </Button>

                    <div class="w-px self-stretch bg-[color:var(--clay-hairline)]" />

                    <template v-if="$page.props.auth.user">
                        <Button as-child>
                            <Link :href="dashboard()">Dashboard</Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button variant="ghost" as-child>
                            <Link :href="login()">Masuk</Link>
                        </Button>
                        <Button v-if="props.canRegister" as-child>
                            <Link :href="register()">Daftar</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <main>
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

                        <div
                            class="mt-8 grid grid-cols-1 gap-3 rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4"
                        >
                            <div class="rounded-md bg-[color:var(--clay-canvas)] p-3">
                                <div class="text-xs font-medium text-[color:var(--clay-muted)]">
                                    Anti double-booking
                                </div>
                                <div class="mt-1 text-sm text-[color:var(--clay-body)]">
                                    Validasi tanggal otomatis
                                </div>
                            </div>
                            <div class="rounded-md bg-[color:var(--clay-canvas)] p-3">
                                <div class="text-xs font-medium text-[color:var(--clay-muted)]">
                                    Foto & gallery
                                </div>
                                <div class="mt-1 text-sm text-[color:var(--clay-body)]">
                                    Upload drag & drop
                                </div>
                            </div>
                            <div class="rounded-md bg-[color:var(--clay-canvas)] p-3">
                                <div class="text-xs font-medium text-[color:var(--clay-muted)]">
                                    Cocok untuk host
                                </div>
                                <div class="mt-1 text-sm text-[color:var(--clay-body)]">
                                    Manage property cepat
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

            <section class="border-t border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-soft)]">
                <div class="mx-auto w-full max-w-md px-6 py-10">
                    <div class="flex flex-col gap-6">
                        <div>
                            <div class="text-sm font-medium text-[color:var(--clay-ink)]">
                                BookMyVilla
                            </div>
                            <div class="mt-1 text-sm text-[color:var(--clay-muted)]">
                                Platform sederhana untuk booking villa harian & manajemen listing.
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Button variant="ghost" as-child>
                                <Link href="/explore">Explore</Link>
                            </Button>
                            <template v-if="$page.props.auth.user">
                                <Button variant="ghost" as-child>
                                    <Link :href="dashboard()">Dashboard</Link>
                                </Button>
                            </template>
                            <template v-else>
                                <Button variant="ghost" as-child>
                                    <Link :href="login()">Masuk</Link>
                                </Button>
                                <Button v-if="props.canRegister" variant="ghost" as-child>
                                    <Link :href="register()">Daftar</Link>
                                </Button>
                            </template>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <nav
            v-if="showBottomNav"
            class="fixed inset-x-0 bottom-0 z-50 border-t border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
        >
            <div class="mx-auto w-full max-w-md px-4 pb-[env(safe-area-inset-bottom)]">
                <div
                    class="grid"
                    :class="[bottomNavItems.length >= 4 ? 'grid-cols-4' : 'grid-cols-3']"
                >
                    <Link
                        v-for="item in bottomNavItems"
                        :key="item.title"
                        :href="item.href"
                        class="flex flex-col items-center justify-center gap-1 py-3 text-xs"
                        :class="[
                            isActive(item)
                                ? 'text-[color:var(--clay-ink)]'
                                : 'text-[color:var(--clay-muted)]',
                        ]"
                    >
                        <component
                            :is="item.icon"
                            class="h-5 w-5"
                            :class="[isActive(item) ? 'opacity-100' : 'opacity-80']"
                        />
                        <span class="leading-none">{{ item.title }}</span>
                    </Link>
                </div>
            </div>
        </nav>
    </div>
</template>
