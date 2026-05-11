<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
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
                title: 'Create',
                href: '/properties/create',
            },
        ],
    },
});

const page = usePage();
const userId = computed(() => (page.props.auth?.user as any)?.id);

const form = useForm({
    owner_id: userId.value,
    type: 'villa',
    name: '',
    address: '',
    description: '',
    investor_name: '',
    status: 'draft',
});

const submit = () => {
    form.post('/properties', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create property" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Create property"
                description="Add a new kost or villa"
            />

            <Button variant="outline" as-child>
                <Link href="/properties">Back</Link>
            </Button>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
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
                <Input id="name" v-model="form.name" placeholder="Property name" />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="address">Address</Label>
                <Input
                    id="address"
                    v-model="form.address"
                    placeholder="Address (optional)"
                />
                <InputError :message="form.errors.address" />
            </div>

            <div class="grid gap-2">
                <Label for="investor_name">Investor</Label>
                <Input
                    id="investor_name"
                    v-model="form.investor_name"
                    placeholder="Investor name (optional)"
                />
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
                    placeholder="Description (optional)"
                />
                <InputError :message="form.errors.description" />
            </div>

            <div class="flex items-center gap-2">
                <Button :disabled="form.processing">Create</Button>
                <Button variant="outline" as-child>
                    <Link href="/properties">Cancel</Link>
                </Button>
            </div>
        </form>
    </div>
</template>

