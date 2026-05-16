<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
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
                title: 'Create',
                href: '/admin/users/create',
            },
        ],
    },
});

const form = useForm({
    name: '',
    email: '',
    role: 'tenant',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/admin/users', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};

const cancel = () => {
    router.visit('/admin/users');
};
</script>

<template>
    <Head title="Create user" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 py-8">
        <div
            class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center"
        >
            <Heading
                variant="default"
                title="Create user"
                description="Create a new user and assign a role"
            />

            <Button variant="outline" type="button" @click="cancel">
                Cancel
            </Button>
        </div>

        <div class="grid gap-6 md:grid-cols-12">
            <form
                class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-6 md:col-span-8 md:col-start-3"
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
                            placeholder="Full name"
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
                            placeholder="email@example.com"
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
                            <option
                                v-for="role in props.roles"
                                :key="role"
                                :value="role"
                            >
                                {{ role }}
                            </option>
                        </select>
                        <InputError :message="form.errors.role" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Password</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            class="clay-control"
                            type="password"
                            autocomplete="new-password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">
                            Confirm password
                        </Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            class="clay-control"
                            type="password"
                            autocomplete="new-password"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <Button :disabled="form.processing">Create</Button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

