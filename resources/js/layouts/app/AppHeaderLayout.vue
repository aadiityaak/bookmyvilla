<script setup lang="ts">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Link, usePage } from '@inertiajs/vue3';
import { Building2, Home, Search, UserRound } from 'lucide-vue-next';
import { computed } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import { Toaster } from '@/components/ui/sonner';
import { toUrl } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

type BottomNavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: any;
    exact?: boolean;
};

const page = usePage();
const user = computed(() => (page.props as any)?.auth?.user ?? null);
const role = computed(() => user.value?.role ?? null);

const showBottomNav = computed(() => true);

const bottomNavItems = computed<BottomNavItem[]>(() => {
    const myPropertyHref = !user.value
        ? '/login'
        : role.value === 'tenant'
            ? '/my-property'
            : role.value === 'admin'
                ? '/my-property'
                : '/properties';

    const accountHref = !user.value ? '/login' : '/profile';

    return [
        { title: 'Home', href: '/', icon: Home, exact: true },
        { title: 'Explore', href: '/explore', icon: Search },
        { title: 'My Property', href: myPropertyHref, icon: Building2 },
        { title: 'Akun', href: accountHref, icon: UserRound },
    ];
});

const isActive = (item: BottomNavItem) => {
    const url = page.url;
    const href = toUrl(item.href);
    if (item.exact) return url === href;
    if (href === '/explore') return url.startsWith('/explore');
    if (href === '/bookings') return url.startsWith('/bookings');
    if (href === '/my-property') return url.startsWith('/my-property') || url.startsWith('/bookings');
    if (href === '/properties') return url.startsWith('/properties');
    if (href === '/admin/properties') return url.startsWith('/admin/properties');
    if (href === '/profile') return url.startsWith('/profile');
    return url.startsWith(href);
};
</script>

<template>
    <AppShell variant="header">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent
            variant="header"
            :class="
                showBottomNav
                    ? 'pt-16 pb-[calc(5.25rem+env(safe-area-inset-bottom))]'
                    : ''
            "
        >
            <slot />
        </AppContent>
        <nav
            v-if="showBottomNav"
            class="fixed inset-x-0 bottom-0 z-[2001] border-t border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
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
                            :class="[
                                isActive(item)
                                    ? 'opacity-100'
                                    : 'opacity-80',
                            ]"
                        />
                        <span class="leading-none">{{ item.title }}</span>
                    </Link>
                </div>
            </div>
        </nav>
        <Toaster />
    </AppShell>
</template>
