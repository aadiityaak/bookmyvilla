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

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 px-6 py-8">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="default"
                title="Edit user"
                description="Update profile, role, and access"
            />

            <Button variant="outline" as-child>
                <Link href="/admin/users">Back</Link>
            </Button>
        </div>

        <div
            class="rounded-3xl border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4"
        >
            <div class="grid gap-2 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-[color:var(--clay-muted)]">User ID</div>
                    <div class="font-medium text-[color:var(--clay-ink)]">
                        {{ user.id }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <div class="text-[color:var(--clay-muted)]">Email verified</div>
                    <div class="font-medium text-[color:var(--clay-ink)]">
                        {{ user.email_verified_at ? 'yes' : 'no' }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <div class="text-[color:var(--clay-muted)]">Created</div>
                    <div class="font-medium text-[color:var(--clay-ink)]">
                        {{ user.created_at ?? '-' }}
                    </div>
                </div>
            </div>
        </div>

        <form
            class="rounded-3xl border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-6"
            @submit.prevent="submit"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        class="clay-control"
                        autocomplete="name"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        class="clay-control"
                        autocomplete="email"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="role">Role</Label>
                    <select
                        id="role"
                        v-model="form.role"
                        class="clay-select"
                    >
                        <option v-for="role in roles" :key="role" :value="role">
                            {{ role }}
                        </option>
                    </select>
                    <InputError :message="form.errors.role" />
                </div>

                <div class="flex items-center justify-between gap-4">
                    <Label for="disabled" class="flex items-center gap-3">
                        <input
                            id="disabled"
                            v-model="form.disabled"
                            type="checkbox"
                            class="h-4 w-4"
                        />
                        <span>Disabled</span>
                    </Label>
                    <InputError :message="form.errors.disabled" />
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button :disabled="form.processing">Save</Button>
                    <Button variant="outline" as-child>
                        <Link href="/admin/users">Cancel</Link>
                    </Button>
                </div>
            </div>
        </form>
    </div>
</template>
