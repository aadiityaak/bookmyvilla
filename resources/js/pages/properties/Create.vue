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

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 py-8">
        <div
            class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center"
        >
            <Heading
                variant="small"
                title="Create property"
                description="Add a new kost or villa"
            />

            <Button variant="outline" as-child>
                <Link href="/properties">Back</Link>
            </Button>
        </div>

        <div class="grid gap-6 md:grid-cols-12">
            <form
                class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-6 md:col-span-8 md:col-start-3"
                @submit.prevent="submit"
            >
                <div class="grid gap-6">
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
                            placeholder="Property name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="address">Address</Label>
                        <Input
                            id="address"
                            v-model="form.address"
                            class="clay-control"
                            placeholder="Address (optional)"
                        />
                        <InputError :message="form.errors.address" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="investor_name">Investor</Label>
                        <Input
                            id="investor_name"
                            v-model="form.investor_name"
                            class="clay-control"
                            placeholder="Investor name (optional)"
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
                            placeholder="Description (optional)"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <Button :disabled="form.processing">Create</Button>
                        <Button variant="outline" as-child>
                            <Link href="/properties">Cancel</Link>
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
