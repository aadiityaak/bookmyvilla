<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    email_verified_at: string | null;
    disabled_at: string | null;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type UsersPaginator = {
    data: UserRow[];
    links: PaginationLink[];
    total: number;
};

const props = defineProps<{
    filters: {
        q?: string;
        role?: string;
        status?: string;
    };
    users: UsersPaginator;
    roles: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: '/admin/users',
            },
        ],
    },
});

const form = useForm({
    q: props.filters.q ?? '',
    role: props.filters.role ?? '',
    status: props.filters.status ?? '',
});

const hasFilters = computed(() => Boolean(form.q || form.role || form.status));

const applyFilters = () => {
    router.get('/admin/users', form.data(), {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    form.q = '';
    form.role = '';
    form.status = '';
    applyFilters();
};

const rolePillClass = (role: string) => {
    switch (role) {
        case 'admin':
            return 'bg-[color:var(--clay-brand-teal)] text-white border-transparent';
        case 'host':
            return 'bg-[color:var(--clay-brand-ochre)] text-black border-transparent';
        case 'investor':
            return 'bg-[color:var(--clay-brand-peach)] text-black border-transparent';
        case 'tenant':
        default:
            return 'bg-[color:var(--clay-brand-lavender)] text-black border-transparent';
    }
};

const statusPillClass = (disabledAt: string | null) => {
    return disabledAt
        ? 'bg-[color:var(--clay-surface-strong)] text-[color:var(--clay-muted)] border-transparent'
        : 'bg-[color:var(--clay-surface-soft)] text-[color:var(--clay-ink)] border-transparent';
};
</script>

<template>
    <Head title="Users" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 py-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <Heading
                variant="default"
                title="Users"
                description="Manage user roles and access"
            />

            <div class="flex items-center gap-2">
                <Badge
                    variant="outline"
                    class="bg-[color:var(--clay-surface-card)] text-[color:var(--clay-body)]"
                >
                    {{ users.total }} total
                </Badge>
                <Button as-child>
                    <Link href="/admin/users/create">Create user</Link>
                </Button>
            </div>
        </div>

        <div
            class="rounded-3xl border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4"
        >
            <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                <div class="md:col-span-6">
                    <Label for="q">Search</Label>
                    <Input
                        id="q"
                        v-model="form.q"
                        class="clay-control"
                        placeholder="Name or email"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="md:col-span-3">
                    <Label for="role">Role</Label>
                    <select
                        id="role"
                        v-model="form.role"
                        class="clay-select"
                        @change="applyFilters"
                    >
                        <option value="">All</option>
                        <option v-for="role in roles" :key="role" :value="role">
                            {{ role }}
                        </option>
                    </select>
                </div>

                <div class="md:col-span-3">
                    <Label for="status">Status</Label>
                    <select
                        id="status"
                        v-model="form.status"
                        class="clay-select"
                        @change="applyFilters"
                    >
                        <option value="">All</option>
                        <option value="active">active</option>
                        <option value="disabled">disabled</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-end gap-2">
                <Button variant="outline" :disabled="!hasFilters" @click="clearFilters">
                    Clear
                </Button>
            </div>
        </div>

        <div
            class="overflow-hidden rounded-3xl border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
        >
            <div class="grid grid-cols-12 gap-3 border-b border-[color:var(--clay-hairline)] px-5 py-3 text-xs font-medium uppercase tracking-wide text-[color:var(--clay-muted)]">
                <div class="col-span-5">User</div>
                <div class="col-span-3">Role</div>
                <div class="col-span-2">Status</div>
                <div class="col-span-2 text-right">Action</div>
            </div>

            <div
                v-for="user in users.data"
                :key="user.id"
                class="grid grid-cols-12 gap-3 border-b border-[color:var(--clay-hairline)] px-5 py-4 text-sm last:border-b-0 hover:bg-[color:var(--clay-surface-soft)]"
            >
                <div class="col-span-5 min-w-0">
                    <div class="truncate font-medium text-[color:var(--clay-ink)]">
                        {{ user.name }}
                    </div>
                    <div class="truncate text-xs text-[color:var(--clay-muted)]">
                        {{ user.email }}
                    </div>
                </div>

                <div class="col-span-3 flex items-center gap-2">
                    <Badge :class="rolePillClass(user.role)">
                        {{ user.role }}
                    </Badge>
                    <Badge
                        v-if="!user.email_verified_at"
                        variant="outline"
                        class="bg-[color:var(--clay-surface-card)] text-[color:var(--clay-muted)]"
                    >
                        unverified
                    </Badge>
                </div>

                <div class="col-span-2">
                    <Badge :class="statusPillClass(user.disabled_at)">
                        {{ user.disabled_at ? 'disabled' : 'active' }}
                    </Badge>
                </div>

                <div class="col-span-2 flex justify-end">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="`/admin/users/${user.id}/edit`">Edit</Link>
                    </Button>
                </div>
            </div>

            <div v-if="users.data.length === 0" class="px-5 py-10 text-sm text-[color:var(--clay-muted)]">
                No users found.
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Link
                v-for="link in users.links"
                :key="link.label"
                :href="link.url ?? ''"
                class="rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] px-3 py-1.5 text-sm"
                :class="[
                    link.active
                        ? 'bg-[color:var(--clay-surface-card)] text-[color:var(--clay-ink)]'
                        : 'text-[color:var(--clay-body)]',
                    !link.url ? 'pointer-events-none opacity-50' : '',
                ]"
            >
                <span v-html="link.label" />
            </Link>
        </div>
    </div>
</template>
