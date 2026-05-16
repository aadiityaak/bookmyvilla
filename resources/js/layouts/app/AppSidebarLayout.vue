<script setup lang="ts">
import { computed } from 'vue';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Link, usePage } from '@inertiajs/vue3';
import { CalendarDays, Home, Search, UserRound } from 'lucide-vue-next';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const isAdminRoute = computed(() => page.url.startsWith('/admin'));
const userRole = computed(() => (page.props.auth?.user as any)?.role);
const showBottomNav = computed(() => userRole.value === 'tenant');

type BottomNavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: any;
    exact?: boolean;
};

const bottomNavItems = computed<BottomNavItem[]>(() => [
    { title: 'Home', href: '/', icon: Home, exact: true },
    { title: 'Explore', href: '/explore', icon: Search },
    { title: 'Bookings', href: '/bookings', icon: CalendarDays },
    { title: 'Akun', href: dashboard(), icon: UserRound },
]);

const isActive = (item: BottomNavItem) => {
    const url = page.url;
    const href = toUrl(item.href);
    if (item.exact) return url === href;
    if (href === '/explore') return url.startsWith('/explore');
    if (href === '/bookings') return url.startsWith('/bookings');
    return url.startsWith(href);
};
</script>

<template>
    <div :class="isAdminRoute ? 'theme-clay' : ''">
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent
                variant="sidebar"
                :class="
                    showBottomNav
                        ? 'overflow-x-hidden pb-[calc(5.25rem+env(safe-area-inset-bottom))]'
                        : 'overflow-x-hidden'
                "
            >
                <AppSidebarHeader :breadcrumbs="breadcrumbs" />
                <slot />
            </AppContent>
            <nav
                v-if="showBottomNav"
                class="fixed inset-x-0 bottom-0 z-50 border-t border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
            >
                <div class="mx-auto w-full max-w-md px-4 pb-[env(safe-area-inset-bottom)]">
                    <div class="grid grid-cols-4">
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
    </div>
</template>
