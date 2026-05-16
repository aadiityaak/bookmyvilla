<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type PropertyData = {
    id: number;
    owner_id: number;
    investor_id: number | null;
    type: string;
    name: string;
    address: string | null;
    description: string | null;
    status: string;
    gallery: string[];
    created_at: string | null;
    updated_at: string | null;
};

const props = defineProps<{
    property: PropertyData;
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
                title: 'Edit',
                href: '/properties',
            },
        ],
    },
});

const ownerQuery = ref('');
const investorQuery = ref('');

const filteredOwners = computed(() => {
    const q = ownerQuery.value.trim().toLowerCase();
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
    const q = investorQuery.value.trim().toLowerCase();
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
    owner_id: props.property.owner_id,
    investor_id: props.property.investor_id,
    type: props.property.type,
    name: props.property.name,
    address: props.property.address ?? '',
    description: props.property.description ?? '',
    status: props.property.status,
    gallery_existing: (props.property.gallery ?? []) as string[],
    gallery_files: [] as File[],
});

const galleryInputRef = ref<HTMLInputElement | null>(null);
const galleryDragOver = ref(false);
const galleryPreviews = ref<{ file: File; url: string }[]>([]);

const galleryUrl = (value: string) => {
    return value.startsWith('http://') || value.startsWith('https://')
        ? value
        : `/storage/${value}`;
};

const removeExistingGalleryItem = (index: number) => {
    form.gallery_existing.splice(index, 1);
};

const onGalleryFilesSelected = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const files = input.files ? Array.from(input.files) : [];
    if (!files.length) return;
    addGalleryFiles(files);
    input.value = '';
};

const addGalleryFiles = (files: File[]) => {
    const imageFiles = files.filter((file) => file.type.startsWith('image/'));
    if (!imageFiles.length) return;

    form.gallery_files = [...form.gallery_files, ...imageFiles];
    galleryPreviews.value = [
        ...galleryPreviews.value,
        ...imageFiles.map((file) => ({ file, url: URL.createObjectURL(file) })),
    ];
};

const onGalleryDrop = (event: DragEvent) => {
    event.preventDefault();
    galleryDragOver.value = false;
    const files = event.dataTransfer?.files ? Array.from(event.dataTransfer.files) : [];
    if (!files.length) return;
    addGalleryFiles(files);
};

const removeGalleryFile = (index: number) => {
    const preview = galleryPreviews.value[index];
    if (preview) {
        URL.revokeObjectURL(preview.url);
        galleryPreviews.value.splice(index, 1);
    }
    form.gallery_files.splice(index, 1);
};

onBeforeUnmount(() => {
    for (const item of galleryPreviews.value) {
        URL.revokeObjectURL(item.url);
    }
});

const submit = () => {
    form.patch(`/properties/${props.property.id}`, {
        preserveScroll: true,
        forceFormData: true,
    });
};

const descriptionEditorRef = ref<HTMLDivElement | null>(null);

const syncDescriptionFromEditor = () => {
    if (!descriptionEditorRef.value) return;
    form.description = descriptionEditorRef.value.innerHTML;
};

const applyDescriptionCommand = (command: string, value?: string) => {
    descriptionEditorRef.value?.focus();
    document.execCommand(command, false, value);
    syncDescriptionFromEditor();
};

const addDescriptionLink = () => {
    const url = window.prompt('Link URL');
    if (!url) return;
    applyDescriptionCommand('createLink', url);
};

onMounted(() => {
    if (!descriptionEditorRef.value) return;
    descriptionEditorRef.value.innerHTML = form.description || '';
});

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
                        <div v-if="canManageAll" class="grid gap-2">
                            <Label for="owner_id">User</Label>
                            <Select v-model="form.owner_id">
                                <SelectTrigger class="clay-select w-full">
                                    <SelectValue placeholder="Select user" />
                                </SelectTrigger>
                                <SelectContent class="w-(--reka-select-trigger-width)">
                                    <div class="p-2">
                                        <Input
                                            v-model="ownerQuery"
                                            class="clay-control h-9"
                                            placeholder="Search user…"
                                        />
                                    </div>
                                    <template v-if="filteredOwners.length">
                                        <SelectItem
                                            v-for="u in filteredOwners"
                                            :key="u.id"
                                            :value="String(u.id)"
                                        >
                                            {{ u.name }} ({{ u.email }}) — {{ u.role }}
                                        </SelectItem>
                                    </template>
                                    <div
                                        v-else
                                        class="px-3 py-2 text-sm text-muted-foreground"
                                    >
                                        No results
                                    </div>
                                </SelectContent>
                            </Select>
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
                            <Label for="investor_id">Investor</Label>
                            <Select v-model="form.investor_id">
                                <SelectTrigger class="clay-select w-full">
                                    <SelectValue placeholder="-" />
                                </SelectTrigger>
                                <SelectContent class="w-(--reka-select-trigger-width)">
                                    <div class="p-2">
                                        <Input
                                            v-model="investorQuery"
                                            class="clay-control h-9"
                                            placeholder="Search investor/host…"
                                        />
                                    </div>
                                    <SelectItem value="">-</SelectItem>
                                    <template v-if="filteredInvestors.length">
                                        <SelectItem
                                            v-for="u in filteredInvestors"
                                            :key="u.id"
                                            :value="String(u.id)"
                                        >
                                            {{ u.name }} ({{ u.email }}) — {{ u.role }}
                                        </SelectItem>
                                    </template>
                                    <div
                                        v-else
                                        class="px-3 py-2 text-sm text-muted-foreground"
                                    >
                                        No results
                                    </div>
                                </SelectContent>
                            </Select>
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
                            <div class="flex flex-wrap items-center gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="applyDescriptionCommand('bold')"
                                >
                                    Bold
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="applyDescriptionCommand('italic')"
                                >
                                    Italic
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="applyDescriptionCommand('underline')"
                                >
                                    Underline
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="applyDescriptionCommand('insertUnorderedList')"
                                >
                                    Bullets
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="applyDescriptionCommand('insertOrderedList')"
                                >
                                    Numbered
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="addDescriptionLink"
                                >
                                    Link
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="applyDescriptionCommand('removeFormat')"
                                >
                                    Clear
                                </Button>
                            </div>
                            <div
                                id="description"
                                ref="descriptionEditorRef"
                                class="clay-textarea min-h-32"
                                contenteditable="true"
                                @input="syncDescriptionFromEditor"
                                @blur="syncDescriptionFromEditor"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <div class="flex items-center justify-between gap-3">
                                <Label>Gallery</Label>
                            </div>

                            <div
                                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-soft)] px-4 py-8 text-center"
                                :class="[
                                    galleryDragOver
                                        ? 'border-[color:var(--clay-ink)]'
                                        : '',
                                ]"
                                @click="galleryInputRef?.click()"
                                @dragenter.prevent="galleryDragOver = true"
                                @dragover.prevent="galleryDragOver = true"
                                @dragleave.prevent="galleryDragOver = false"
                                @drop="onGalleryDrop"
                            >
                                <div class="text-sm font-medium text-[color:var(--clay-ink)]">
                                    Choose files or drag & drop here
                                </div>
                                <div class="text-xs text-[color:var(--clay-muted)]">
                                    PNG/JPG up to 50MB each
                                </div>
                                <Button type="button" variant="outline">
                                    Browse files
                                </Button>
                                <input
                                    ref="galleryInputRef"
                                    id="gallery_files"
                                    type="file"
                                    accept="image/*"
                                    multiple
                                    class="hidden"
                                    @change="onGalleryFilesSelected"
                                />
                            </div>

                            <div v-if="form.gallery_existing.length" class="grid grid-cols-2 gap-3 md:grid-cols-3">
                                <div
                                    v-for="(item, index) in form.gallery_existing"
                                    :key="`${item}-${index}`"
                                    class="relative overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
                                >
                                    <img
                                        :src="galleryUrl(item)"
                                        alt=""
                                        class="h-28 w-full object-cover"
                                    />
                                    <div class="px-3 py-2">
                                        <div class="truncate text-sm font-medium text-[color:var(--clay-ink)]">
                                            {{ item }}
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="absolute top-2 right-2 bg-[color:var(--clay-canvas)]"
                                        @click="removeExistingGalleryItem(index)"
                                    >
                                        Remove
                                    </Button>
                                </div>
                            </div>

                            <div v-if="galleryPreviews.length" class="grid grid-cols-2 gap-3 md:grid-cols-3">
                                <div
                                    v-for="(item, index) in galleryPreviews"
                                    :key="`${item.file.name}-${index}`"
                                    class="relative overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
                                >
                                    <img
                                        :src="item.url"
                                        alt=""
                                        class="h-28 w-full object-cover"
                                    />
                                    <div class="flex items-center justify-between gap-2 px-3 py-2">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-medium text-[color:var(--clay-ink)]">
                                                {{ item.file.name }}
                                            </div>
                                            <div class="text-xs text-[color:var(--clay-muted)]">
                                                {{ Math.round(item.file.size / 1024) }} KB
                                            </div>
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="absolute top-2 right-2 bg-[color:var(--clay-canvas)]"
                                        @click="removeGalleryFile(index)"
                                    >
                                        Remove
                                    </Button>
                                </div>
                            </div>
                            <InputError :message="form.errors.gallery_files" />
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
