<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, BookOpen, Folder, LayoutGrid, Menu, Search } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { toUrl } from '@/lib/utils';
import { dashboard, login, register } from '@/routes';
import type { BreadcrumbItem, NavItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const { isCurrentUrl, whenCurrentUrl } = useCurrentUrl();
const canRegister = computed(() => Boolean((page.props as any)?.canRegister));
const notifications = computed(
    () => (page.props as any)?.notifications ?? { unread_count: 0, items: [] },
);
const unreadCount = computed(() => Number(notifications.value?.unread_count ?? 0));
const notificationItems = computed(() =>
    Array.isArray(notifications.value?.items) ? notifications.value.items : [],
);

const formatDateTime = (iso?: string | null) => {
    if (!iso) return '';
    const d = new Date(iso);
    if (!Number.isFinite(d.getTime())) return '';
    return d.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const onBellOpenChange = (open: boolean) => {
    if (!open) return;
    router.reload({ only: ['notifications'], preserveScroll: true, preserveState: true });
};

const isPublicHeader = computed(
    () => !page.url.startsWith('/admin') && !page.url.startsWith('/settings'),
);

const logoHref = computed(() => '/');

const activeItemStyles = 'text-neutral-900';

const isMobileOnly = computed(
    () => !auth.value?.user || auth.value?.user?.role === 'tenant',
);

const mainNavItems = computed<NavItem[]>(() => {
    if (!auth.value?.user) {
        return [{ title: 'Explore', href: '/explore', icon: Search }];
    }

    if (auth.value?.user?.role === 'tenant') {
        return [
            { title: 'Explore', href: '/explore', icon: Search },
            { title: 'Bookings', href: '/bookings', icon: LayoutGrid },
        ];
    }

    return [{ title: 'Dashboard', href: dashboard(), icon: LayoutGrid }];
});

const rightNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <div
        v-if="isPublicHeader"
        class="fixed inset-x-0 top-0 z-[2000] border-b border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
    >
        <div class="mx-auto flex h-16 w-full max-w-md items-center justify-between px-6">
            <Link :href="logoHref" class="flex items-center gap-2">
                <AppLogo variant="plain" />
            </Link>

            <div class="flex items-center gap-2">
                <DropdownMenu v-if="auth.user" @update:open="onBellOpenChange">
                    <DropdownMenuTrigger :as-child="true">
                        <Button variant="ghost" size="icon" class="relative h-9 w-9">
                            <Bell class="h-5 w-5 opacity-80" />
                            <span
                                v-if="unreadCount > 0"
                                class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-[color:var(--primary)] px-1 text-[10px] font-semibold leading-none text-[color:var(--primary-foreground)]"
                            >
                                {{ unreadCount > 9 ? '9+' : unreadCount }}
                            </span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-80">
                        <div class="flex items-center justify-between gap-3 px-2 py-1.5">
                            <DropdownMenuLabel class="p-0">Notifikasi</DropdownMenuLabel>
                            <Link
                                v-if="unreadCount > 0"
                                href="/notifications/read-all"
                                method="post"
                                as="button"
                                class="text-xs text-[color:var(--primary)]"
                            >
                                Tandai semua dibaca
                            </Link>
                        </div>
                        <DropdownMenuSeparator />
                        <div v-if="notificationItems.length === 0" class="px-2 py-3 text-sm text-[color:var(--clay-muted)]">
                            Belum ada notifikasi.
                        </div>
                        <template v-else>
                            <DropdownMenuItem
                                v-for="n in notificationItems"
                                :key="n.id"
                                class="p-0"
                            >
                                <Link
                                    :href="`/notifications/${n.id}/read`"
                                    method="post"
                                    :data="{ redirect: n.url ?? '' }"
                                    as="button"
                                    class="flex w-full flex-col gap-0.5 px-2 py-2 text-left"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 text-sm font-medium text-[color:var(--clay-ink)]">
                                            {{ n.title }}
                                        </div>
                                        <span
                                            v-if="!n.read_at"
                                            class="mt-1 h-2 w-2 shrink-0 rounded-full bg-[color:var(--primary)]"
                                        />
                                    </div>
                                    <div v-if="n.body" class="text-xs text-[color:var(--clay-muted)]">
                                        {{ n.body }}
                                    </div>
                                    <div class="text-[11px] text-[color:var(--clay-muted)]">
                                        {{ formatDateTime(n.created_at) }}
                                    </div>
                                </Link>
                            </DropdownMenuItem>
                        </template>
                    </DropdownMenuContent>
                </DropdownMenu>

                <DropdownMenu v-if="auth.user">
                    <DropdownMenuTrigger :as-child="true">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                        >
                            <Avatar class="size-8 overflow-hidden rounded-full">
                                <AvatarImage
                                    v-if="auth.user?.avatar"
                                    :src="String(auth.user?.avatar).startsWith('http') ? auth.user?.avatar : `/storage/${auth.user?.avatar}`"
                                    :alt="String(auth.user?.name ?? '')"
                                />
                                <AvatarFallback
                                    class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                >
                                    {{ getInitials(auth.user?.name) }}
                                </AvatarFallback>
                            </Avatar>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent :user="auth.user" />
                    </DropdownMenuContent>
                </DropdownMenu>

                <Button v-else :variant="page.url.startsWith('/login') && canRegister ? 'outline' : 'default'" as-child>
                    <Link
                        v-if="page.url.startsWith('/login') && canRegister"
                        :href="register()"
                    >
                        Daftar
                    </Link>
                    <Link v-else :href="login()">Masuk</Link>
                </Button>
            </div>
        </div>
    </div>

    <div v-else>
        <div class="border-b border-sidebar-border/80">
            <div
                class="mx-auto flex h-16 items-center px-4"
                :class="[isMobileOnly ? 'max-w-md' : 'md:max-w-7xl']"
            >
                <div :class="[isMobileOnly ? '' : 'lg:hidden']">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-2 h-9 w-9"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-[300px] p-6">
                            <SheetTitle class="sr-only"
                                >Navigation menu</SheetTitle
                            >
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogoIcon
                                    class="size-6 fill-current text-black dark:text-white"
                                />
                            </SheetHeader>
                            <div
                                class="flex h-full flex-1 flex-col justify-between space-y-4 py-6"
                            >
                                <nav class="-mx-3 space-y-1">
                                    <Link
                                        v-for="item in mainNavItems"
                                        :key="item.title"
                                        :href="item.href"
                                        class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                        :class="
                                            whenCurrentUrl(
                                                item.href,
                                                activeItemStyles,
                                            )
                                        "
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="h-5 w-5"
                                        />
                                        {{ item.title }}
                                    </Link>
                                </nav>
                                <div v-if="!isMobileOnly" class="flex flex-col space-y-4">
                                    <a
                                        v-for="item in rightNavItems"
                                        :key="item.title"
                                        :href="toUrl(item.href)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center space-x-2 text-sm font-medium"
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="h-5 w-5"
                                        />
                                        <span>{{ item.title }}</span>
                                    </a>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="logoHref" class="flex items-center gap-x-2">
                    <AppLogo />
                </Link>

                <div v-if="!isMobileOnly" class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu class="ml-10 flex h-full items-stretch">
                        <NavigationMenuList
                            class="flex h-full items-stretch space-x-2"
                        >
                            <NavigationMenuItem
                                v-for="(item, index) in mainNavItems"
                                :key="index"
                                class="relative flex h-full items-center"
                            >
                                <Link
                                    :class="[
                                        navigationMenuTriggerStyle(),
                                        whenCurrentUrl(
                                            item.href,
                                            activeItemStyles,
                                        ),
                                        'h-9 cursor-pointer px-3',
                                    ]"
                                    :href="item.href"
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                        class="mr-2 h-4 w-4"
                                    />
                                    {{ item.title }}
                                </Link>
                                <div
                                    v-if="isCurrentUrl(item.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"
                                ></div>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <div v-if="!isMobileOnly" class="relative flex items-center space-x-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="group h-9 w-9 cursor-pointer"
                        >
                            <Search
                                class="size-5 opacity-80 group-hover:opacity-100"
                            />
                        </Button>

                        <div class="hidden space-x-1 lg:flex">
                            <template
                                v-for="item in rightNavItems"
                                :key="item.title"
                            >
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                as-child
                                                class="group h-9 w-9 cursor-pointer"
                                            >
                                                <a
                                                    :href="toUrl(item.href)"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <span class="sr-only">{{
                                                        item.title
                                                    }}</span>
                                                    <component
                                                        :is="item.icon"
                                                        class="size-5 opacity-80 group-hover:opacity-100"
                                                    />
                                                </a>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{{ item.title }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </template>
                        </div>
                    </div>

                    <DropdownMenu v-if="auth.user" @update:open="onBellOpenChange">
                        <DropdownMenuTrigger :as-child="true">
                            <Button variant="ghost" size="icon" class="relative h-9 w-9">
                                <Bell class="size-5 opacity-80" />
                                <span
                                    v-if="unreadCount > 0"
                                    class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-[color:var(--primary)] px-1 text-[10px] font-semibold leading-none text-[color:var(--primary-foreground)]"
                                >
                                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                                </span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-80">
                            <div class="flex items-center justify-between gap-3 px-2 py-1.5">
                                <DropdownMenuLabel class="p-0">Notifikasi</DropdownMenuLabel>
                                <Link
                                    v-if="unreadCount > 0"
                                    href="/notifications/read-all"
                                    method="post"
                                    as="button"
                                    class="text-xs text-[color:var(--primary)]"
                                >
                                    Tandai semua dibaca
                                </Link>
                            </div>
                            <DropdownMenuSeparator />
                            <div v-if="notificationItems.length === 0" class="px-2 py-3 text-sm text-[color:var(--clay-muted)]">
                                Belum ada notifikasi.
                            </div>
                            <template v-else>
                                <DropdownMenuItem
                                    v-for="n in notificationItems"
                                    :key="n.id"
                                    class="p-0"
                                >
                                    <Link
                                        :href="`/notifications/${n.id}/read`"
                                        method="post"
                                        :data="{ redirect: n.url ?? '' }"
                                        as="button"
                                        class="flex w-full flex-col gap-0.5 px-2 py-2 text-left"
                                    >
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0 text-sm font-medium text-[color:var(--clay-ink)]">
                                                {{ n.title }}
                                            </div>
                                            <span
                                                v-if="!n.read_at"
                                                class="mt-1 h-2 w-2 shrink-0 rounded-full bg-[color:var(--primary)]"
                                            />
                                        </div>
                                        <div v-if="n.body" class="text-xs text-[color:var(--clay-muted)]">
                                            {{ n.body }}
                                        </div>
                                        <div class="text-[11px] text-[color:var(--clay-muted)]">
                                            {{ formatDateTime(n.created_at) }}
                                        </div>
                                    </Link>
                                </DropdownMenuItem>
                            </template>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <DropdownMenu v-if="auth.user">
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar class="size-8 overflow-hidden rounded-full">
                                    <AvatarImage
                                        v-if="auth.user?.avatar"
                                        :src="auth.user?.avatar"
                                        :alt="String(auth.user?.name ?? '')"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <Button v-else as-child>
                        <Link :href="login()">Log in</Link>
                    </Button>
                </div>
            </div>
        </div>

        <div
            v-if="props.breadcrumbs.length > 1"
            class="flex w-full border-b border-sidebar-border/70"
        >
            <div
                class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500"
                :class="[isMobileOnly ? 'max-w-md' : 'md:max-w-7xl']"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
