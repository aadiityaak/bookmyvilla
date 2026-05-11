<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
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
</script>

<template>
    <Head title="Users" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Users"
                description="Manage user roles and access"
            />

            <Button as-child>
                <Link href="/admin/users/create">Create user</Link>
            </Button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
            <div class="md:col-span-5">
                <Label for="q">Search</Label>
                <Input
                    id="q"
                    v-model="form.q"
                    placeholder="Name or email"
                    @keyup.enter="applyFilters"
                />
            </div>

            <div class="md:col-span-3">
                <Label for="role">Role</Label>
                <select
                    id="role"
                    v-model="form.role"
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
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
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                    @change="applyFilters"
                >
                    <option value="">All</option>
                    <option value="active">active</option>
                    <option value="disabled">disabled</option>
                </select>
            </div>

            <div class="flex items-end gap-2 md:col-span-1">
                <Button
                    variant="outline"
                    class="w-full"
                    :disabled="!hasFilters"
                    @click="clearFilters"
                >
                    Clear
                </Button>
            </div>
        </div>

        <div class="rounded-xl border">
            <div
                class="grid grid-cols-12 gap-3 border-b px-4 py-3 text-sm font-medium"
            >
                <div class="col-span-4">Name</div>
                <div class="col-span-4">Email</div>
                <div class="col-span-2">Role</div>
                <div class="col-span-1">Status</div>
                <div class="col-span-1 text-right">Action</div>
            </div>

            <div
                v-for="user in users.data"
                :key="user.id"
                class="grid grid-cols-12 gap-3 px-4 py-3 text-sm"
            >
                <div class="col-span-4 truncate">{{ user.name }}</div>
                <div class="col-span-4 truncate">{{ user.email }}</div>
                <div class="col-span-2">{{ user.role }}</div>
                <div class="col-span-1">
                    {{ user.disabled_at ? 'disabled' : 'active' }}
                </div>
                <div class="col-span-1 flex justify-end">
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="`/admin/users/${user.id}/edit`">Edit</Link>
                    </Button>
                </div>
            </div>

            <div v-if="users.data.length === 0" class="px-4 py-6 text-sm">
                No users found.
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Link
                v-for="link in users.links"
                :key="link.label"
                :href="link.url ?? ''"
                class="rounded-md border px-3 py-1 text-sm"
                :class="[
                    link.active
                        ? 'bg-accent text-accent-foreground'
                        : 'bg-background',
                    !link.url ? 'pointer-events-none opacity-50' : '',
                ]"
            >
                <span v-html="link.label" />
            </Link>
            <span class="ml-auto text-sm text-muted-foreground">
                Total: {{ users.total }}
            </span>
        </div>
    </div>
</template>
