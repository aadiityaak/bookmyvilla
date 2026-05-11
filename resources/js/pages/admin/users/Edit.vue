<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type UserData = {
    id: number;
    name: string;
    email: string;
    role: string;
    email_verified_at: string | null;
    disabled_at: string | null;
    created_at: string | null;
};

const props = defineProps<{
    user: UserData;
    roles: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: '/admin/users',
            },
            {
                title: 'Edit',
                href: '/admin/users',
            },
        ],
    },
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    role: props.user.role,
    disabled: Boolean(props.user.disabled_at),
});

const submit = () => {
    form.patch(`/admin/users/${props.user.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Edit user" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Edit user"
                description="Update profile, role, and access"
            />

            <Button variant="outline" as-child>
                <Link href="/admin/users">Back</Link>
            </Button>
        </div>

        <div class="rounded-xl border p-4">
            <div class="grid gap-2 text-sm">
                <div class="flex gap-2">
                    <div class="w-40 text-muted-foreground">User ID</div>
                    <div>{{ user.id }}</div>
                </div>
                <div class="flex gap-2">
                    <div class="w-40 text-muted-foreground">Email verified</div>
                    <div>
                        {{ user.email_verified_at ? 'yes' : 'no' }}
                    </div>
                </div>
                <div class="flex gap-2">
                    <div class="w-40 text-muted-foreground">Created</div>
                    <div>{{ user.created_at ?? '-' }}</div>
                </div>
            </div>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" autocomplete="name" />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" autocomplete="email" />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="role">Role</Label>
                <select
                    id="role"
                    v-model="form.role"
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                >
                    <option v-for="role in roles" :key="role" :value="role">
                        {{ role }}
                    </option>
                </select>
                <InputError :message="form.errors.role" />
            </div>

            <div class="flex items-center gap-2">
                <input
                    id="disabled"
                    v-model="form.disabled"
                    type="checkbox"
                    class="h-4 w-4"
                />
                <Label for="disabled">Disabled</Label>
                <InputError :message="form.errors.disabled" />
            </div>

            <div class="flex items-center gap-2">
                <Button :disabled="form.processing">Save</Button>
                <Button variant="outline" as-child>
                    <Link href="/admin/users">Cancel</Link>
                </Button>
            </div>
        </form>
    </div>
</template>

