<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    types: string[];
    statuses: string[];
    canManageAll: boolean;
    owners: { id: number; name: string; email: string; role: string }[];
    investors: { id: number; name: string; email: string; role: string }[];
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
const ownerSearch = ref('');
const investorSearch = ref('');

const filteredOwners = computed(() => {
    const q = ownerSearch.value.trim().toLowerCase();
    if (!q) return props.owners;
    return props.owners.filter((u) => {
        return (
            u.name.toLowerCase().includes(q) ||
            u.email.toLowerCase().includes(q) ||
            u.role.toLowerCase().includes(q)
        );
    });
});

const filteredInvestors = computed(() => {
    const q = investorSearch.value.trim().toLowerCase();
    if (!q) return props.investors;
    return props.investors.filter((u) => {
        return (
            u.name.toLowerCase().includes(q) ||
            u.email.toLowerCase().includes(q) ||
            u.role.toLowerCase().includes(q)
        );
    });
});

const form = useForm({
    owner_id: userId.value,
    investor_id: null as number | null,
    type: 'villa',
    name: '',
    address: '',
    description: '',
    status: 'draft',
    gallery: [] as string[],
});

const addGalleryItem = () => {
    form.gallery.push('');
};

const removeGalleryItem = (index: number) => {
    form.gallery.splice(index, 1);
};

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
                    <div v-if="canManageAll" class="grid gap-2">
                        <Label for="owner_id">User</Label>
                        <Input
                            id="owner_search"
                            v-model="ownerSearch"
                            class="clay-control"
                            placeholder="Search user by name/email/role"
                        />
                        <select
                            id="owner_id"
                            v-model="form.owner_id"
                            class="clay-select"
                        >
                            <option
                                v-for="u in filteredOwners"
                                :key="u.id"
                                :value="u.id"
                            >
                                {{ u.name }} ({{ u.email }}) — {{ u.role }}
                            </option>
                        </select>
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
                        <Label for="investor_id">Investor</Label>
                        <Input
                            id="investor_search"
                            v-model="investorSearch"
                            class="clay-control"
                            placeholder="Search investor/host by name/email"
                        />
                        <select
                            id="investor_id"
                            v-model="form.investor_id"
                            class="clay-select"
                        >
                            <option :value="null">-</option>
                            <option
                                v-for="u in filteredInvestors"
                                :key="u.id"
                                :value="u.id"
                            >
                                {{ u.name }} ({{ u.email }}) — {{ u.role }}
                            </option>
                        </select>
                        <InputError :message="form.errors.investor_id" />
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

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <Label>Gallery</Label>
                            <Button
                                type="button"
                                variant="outline"
                                @click="addGalleryItem"
                            >
                                Add
                            </Button>
                        </div>

                        <div v-if="form.gallery.length" class="grid gap-2">
                            <div
                                v-for="(item, index) in form.gallery"
                                :key="index"
                                class="flex items-center gap-2"
                            >
                                <Input
                                    :id="`gallery_${index}`"
                                    v-model="form.gallery[index]"
                                    class="clay-control"
                                    placeholder="Image URL or path"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="removeGalleryItem(index)"
                                >
                                    Remove
                                </Button>
                            </div>
                        </div>
                        <InputError :message="form.errors.gallery" />
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
