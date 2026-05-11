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

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
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

        <div class="rounded-xl border p-4">
            <div class="grid gap-2 text-sm">
                <div class="flex gap-2">
                    <div class="w-40 text-muted-foreground">Property ID</div>
                    <div>{{ property.id }}</div>
                </div>
                <div class="flex gap-2">
                    <div class="w-40 text-muted-foreground">Created</div>
                    <div>{{ property.created_at ?? '-' }}</div>
                </div>
                <div class="flex gap-2">
                    <div class="w-40 text-muted-foreground">Updated</div>
                    <div>{{ property.updated_at ?? '-' }}</div>
                </div>
            </div>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div v-if="isAdmin" class="grid gap-2">
                <Label for="owner_id">Owner user ID</Label>
                <Input
                    id="owner_id"
                    v-model="form.owner_id"
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
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                >
                    <option v-for="t in types" :key="t" :value="t">
                        {{ t }}
                    </option>
                </select>
                <InputError :message="form.errors.type" />
            </div>

            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="address">Address</Label>
                <Input id="address" v-model="form.address" />
                <InputError :message="form.errors.address" />
            </div>

            <div class="grid gap-2">
                <Label for="investor_name">Investor</Label>
                <Input id="investor_name" v-model="form.investor_name" />
                <InputError :message="form.errors.investor_name" />
            </div>

            <div class="grid gap-2">
                <Label for="status">Status</Label>
                <select
                    id="status"
                    v-model="form.status"
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
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
                    class="min-h-28 w-full rounded-md border bg-background px-3 py-2 text-sm"
                />
                <InputError :message="form.errors.description" />
            </div>

            <div class="flex items-center gap-2">
                <Button :disabled="form.processing">Save</Button>
                <Button variant="outline" as-child>
                    <Link href="/properties">Cancel</Link>
                </Button>
            </div>
        </form>
    </div>
</template>

