<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type PropertyData = {
    id: number;
    owner_id: number;
    type: string;
    name: string;
    address: string | null;
    description: string | null;
    investor_name: string | null;
    status: string;
    created_at: string | null;
    updated_at: string | null;
};

const props = defineProps<{
    property: PropertyData;
    types: string[];
    statuses: string[];
    canManageAll: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Property',
                href: '/properties',
            },
            {
                title: 'Edit',
                href: '/properties',
            },
        ],
    },
});

const page = usePage();
const role = computed(() => (page.props.auth?.user as any)?.role);
const isAdmin = computed(() => role.value === 'admin');

const form = useForm({
    owner_id: props.property.owner_id,
    type: props.property.type,
    name: props.property.name,
    address: props.property.address ?? '',
    description: props.property.description ?? '',
    investor_name: props.property.investor_name ?? '',
    status: props.property.status,
});

const submit = () => {
    form.patch(`/properties/${props.property.id}`, {
        preserveScroll: true,
    });
};

const destroy = () => {
    if (!confirm('Delete this property?')) return;
    form.delete(`/properties/${props.property.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Edit property" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 py-8">
        <div
            class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center"
        >
            <Heading
                variant="small"
                title="Edit property"
                description="Update property information"
            />

            <div class="flex items-center gap-2">
                <Button variant="outline" as-child>
                    <Link href="/properties">Back</Link>
                </Button>
                <Button variant="destructive" @click="destroy" :disabled="form.processing">
                    Delete
                </Button>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-12">
            <div class="md:col-span-4">
                <div
                    class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4"
                >
                    <div class="grid gap-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-[color:var(--clay-muted)]">
                                Property ID
                            </div>
                            <div class="font-medium text-[color:var(--clay-ink)]">
                                {{ property.id }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-[color:var(--clay-muted)]">
                                Created
                            </div>
                            <div class="font-medium text-[color:var(--clay-ink)]">
                                {{ property.created_at ?? '-' }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-[color:var(--clay-muted)]">
                                Updated
                            </div>
                            <div class="font-medium text-[color:var(--clay-ink)]">
                                {{ property.updated_at ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-8">
                <form
                    class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-6"
                    @submit.prevent="submit"
                >
                    <div class="grid gap-6">
                        <div v-if="isAdmin" class="grid gap-2">
                            <Label for="owner_id">Owner user ID</Label>
                            <Input
                                id="owner_id"
                                v-model="form.owner_id"
                                class="clay-control"
                                type="number"
                                inputmode="numeric"
                            />
                            <InputError :message="form.errors.owner_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="type">Type</Label>
                            <select
                                id="type"
                                v-model="form.type"
                                class="clay-select"
                            >
                                <option v-for="t in types" :key="t" :value="t">
                                    {{ t }}
                                </option>
                            </select>
                            <InputError :message="form.errors.type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                class="clay-control"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="address">Address</Label>
                            <Input
                                id="address"
                                v-model="form.address"
                                class="clay-control"
                            />
                            <InputError :message="form.errors.address" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="investor_name">Investor</Label>
                            <Input
                                id="investor_name"
                                v-model="form.investor_name"
                                class="clay-control"
                            />
                            <InputError :message="form.errors.investor_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="clay-select"
                            >
                                <option v-for="s in statuses" :key="s" :value="s">
                                    {{ s }}
                                </option>
                            </select>
                            <InputError :message="form.errors.status" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="clay-textarea"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <Button :disabled="form.processing">Save</Button>
                            <Button variant="outline" as-child>
                                <Link href="/properties">Cancel</Link>
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
