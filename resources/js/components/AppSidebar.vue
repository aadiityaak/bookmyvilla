<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, Building2, CalendarDays, FileText, FolderGit2, LayoutGrid, Settings, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const userRole = computed(() => (page.props.auth?.user as any)?.role);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        ...(userRole.value === 'tenant'
            ? []
            : [
                  {
                      title: 'Property',
                      href: userRole.value === 'admin' ? '/admin/properties' : '/properties',
                      icon: Building2,
                  },
              ]),
        ...(userRole.value === 'tenant'
            ? [
                  {
                      title: 'Bookings',
                      href: '/bookings',
                      icon: CalendarDays,
                  },
              ]
            : []),
    ];

    if (userRole.value === 'admin') {
        items.push({
            title: 'Articles',
            href: '/admin/articles',
            icon: FileText,
        });
        items.push({
            title: 'Setting',
            href: '/admin/settings/branding',
            icon: Settings,
        });
        items.push({
            title: 'Users',
            href: '/admin/users',
            icon: Users,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
