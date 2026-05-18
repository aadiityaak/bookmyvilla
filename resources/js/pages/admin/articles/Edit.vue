<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { QuillEditor } from '@vueup/vue-quill';

type Article = {
    id: number;
    title: string;
    slug: string;
    featured_image: string | null;
    excerpt: string | null;
    content: string | null;
    status: string;
    published_at: string | null;
    created_at: string | null;
    updated_at: string | null;
};

const props = defineProps<{
    article: Article;
    statuses: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Articles',
                href: '/admin/articles',
            },
            {
                title: 'Edit',
                href: '/admin/articles',
            },
        ],
    },
});

const isClient = ref(false);
const slugTouched = ref(false);

const toSlug = (value: string) =>
    value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)+/g, '');

const pad2 = (n: number) => String(n).padStart(2, '0');

const toDatetimeLocal = (iso: string | null) => {
    if (!iso) return '';
    const d = new Date(iso);
    if (!Number.isFinite(d.getTime())) return '';
    return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}T${pad2(d.getHours())}:${pad2(d.getMinutes())}`;
};

const imageUrl = (path: string | null) => {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const form = useForm({
    title: props.article.title ?? '',
    slug: props.article.slug ?? '',
    featured_image_remove: false,
    featured_image_file: null as File | null,
    excerpt: props.article.excerpt ?? '',
    content: props.article.content ?? '',
    status: props.article.status ?? 'draft',
    published_at: toDatetimeLocal(props.article.published_at),
});

const existingFeaturedImageUrl = computed(() => imageUrl(props.article.featured_image));

const featuredImageInputRef = ref<HTMLInputElement | null>(null);
const featuredImageDragOver = ref(false);
const featuredImagePreviewUrl = ref<string | null>(null);

const setFeaturedImage = (file: File | null) => {
    if (featuredImagePreviewUrl.value) {
        URL.revokeObjectURL(featuredImagePreviewUrl.value);
        featuredImagePreviewUrl.value = null;
    }

    form.featured_image_file = file;

    if (file) {
        form.featured_image_remove = false;
        featuredImagePreviewUrl.value = URL.createObjectURL(file);
    }
};

const onFeaturedImageSelected = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    if (file && file.type.startsWith('image/')) {
        setFeaturedImage(file);
    }
    input.value = '';
};

const onFeaturedImageDrop = (event: DragEvent) => {
    event.preventDefault();
    featuredImageDragOver.value = false;
    const file = event.dataTransfer?.files?.[0] ?? null;
    if (file && file.type.startsWith('image/')) {
        setFeaturedImage(file);
    }
};

const removeFeaturedImage = () => {
    setFeaturedImage(null);
    form.featured_image_remove = true;
};

const canPickPublishedAt = computed(() => form.status === 'published');

watch(
    () => form.title,
    (title) => {
        if (slugTouched.value) return;
        if (String(form.slug ?? '').trim() !== '') return;
        form.slug = toSlug(String(title ?? ''));
    },
);

watch(
    () => form.status,
    (status) => {
        if (status !== 'published') {
            form.published_at = '';
        }
    },
);

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post(`/admin/articles/${props.article.id}`, {
        preserveScroll: true,
        forceFormData: true,
    });
};

const destroy = () => {
    if (!confirm('Delete this article?')) return;
    form.delete(`/admin/articles/${props.article.id}`, {
        preserveScroll: true,
    });
};

onMounted(() => {
    isClient.value = true;
});

onBeforeUnmount(() => {
    if (featuredImagePreviewUrl.value) {
        URL.revokeObjectURL(featuredImagePreviewUrl.value);
    }
});
</script>

<template>
    <Head title="Edit Article" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 py-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <Heading variant="small" title="Edit article" description="Update artikel" />

            <div class="flex items-center gap-2">
                <Button variant="outline" as-child>
                    <Link href="/admin/articles">Back</Link>
                </Button>
                <Button variant="destructive" @click="destroy" :disabled="form.processing">
                    Delete
                </Button>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-12">
            <div class="md:col-span-4">
                <div class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4">
                    <div class="grid gap-2 text-sm">
                        <div v-if="!form.featured_image_remove && (featuredImagePreviewUrl || existingFeaturedImageUrl)" class="overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]">
                            <img
                                :src="featuredImagePreviewUrl || existingFeaturedImageUrl || ''"
                                alt=""
                                class="h-36 w-full object-cover"
                            />
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-[color:var(--clay-muted)]">Article ID</div>
                            <div class="font-medium text-[color:var(--clay-ink)]">
                                {{ props.article.id }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-[color:var(--clay-muted)]">Status</div>
                            <Badge
                                variant="outline"
                                class="bg-[color:var(--clay-surface-soft)] text-[color:var(--clay-ink)]"
                            >
                                {{ form.status }}
                            </Badge>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-[color:var(--clay-muted)]">Slug</div>
                            <div class="truncate font-medium text-[color:var(--clay-ink)]">
                                {{ form.slug || '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-8">
                <form
                    class="grid gap-6 rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-6"
                    @submit.prevent="submit"
                >
                    <div class="grid gap-2">
                        <Label for="title">Title</Label>
                        <Input id="title" v-model="form.title" class="clay-control" />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="slug">Slug (optional)</Label>
                        <Input
                            id="slug"
                            v-model="form.slug"
                            class="clay-control"
                            @input="slugTouched = true"
                        />
                        <InputError :message="form.errors.slug" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                        <div class="grid gap-2 md:col-span-6">
                            <Label for="status">Status</Label>
                            <select id="status" v-model="form.status" class="clay-select">
                                <option v-for="status in props.statuses" :key="status" :value="status">
                                    {{ status }}
                                </option>
                            </select>
                            <InputError :message="form.errors.status" />
                        </div>

                        <div class="grid gap-2 md:col-span-6">
                            <Label for="published_at">Published at</Label>
                            <input
                                id="published_at"
                                v-model="form.published_at"
                                type="datetime-local"
                                class="clay-control"
                                :disabled="!canPickPublishedAt"
                            />
                            <InputError :message="form.errors.published_at" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <Label>Featured image</Label>
                            <Button
                                v-if="(featuredImagePreviewUrl || existingFeaturedImageUrl) && !form.featured_image_remove"
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="removeFeaturedImage"
                            >
                                Remove
                            </Button>
                        </div>

                        <div
                            class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-soft)] px-4 py-8 text-center"
                            :class="[
                                featuredImageDragOver
                                    ? 'border-[color:var(--clay-ink)]'
                                    : '',
                            ]"
                            @click="featuredImageInputRef?.click()"
                            @dragenter.prevent="featuredImageDragOver = true"
                            @dragover.prevent="featuredImageDragOver = true"
                            @dragleave.prevent="featuredImageDragOver = false"
                            @drop="onFeaturedImageDrop"
                        >
                            <div class="text-sm font-medium text-[color:var(--clay-ink)]">
                                Choose a file or drag & drop here
                            </div>
                            <div class="text-xs text-[color:var(--clay-muted)]">
                                PNG/JPG up to 50MB
                            </div>
                            <Button type="button" variant="outline">
                                Browse file
                            </Button>
                            <input
                                ref="featuredImageInputRef"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="onFeaturedImageSelected"
                            />
                        </div>

                        <div v-if="featuredImagePreviewUrl" class="relative overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]">
                            <img
                                :src="featuredImagePreviewUrl"
                                alt=""
                                class="h-44 w-full object-cover"
                            />
                        </div>

                        <InputError :message="form.errors.featured_image_file" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="excerpt">Excerpt</Label>
                        <textarea
                            id="excerpt"
                            v-model="form.excerpt"
                            class="clay-textarea"
                            rows="4"
                            placeholder="Ringkasan singkat artikel"
                        />
                        <InputError :message="form.errors.excerpt" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Content</Label>
                        <div v-if="isClient" class="clay-wysiwyg">
                            <QuillEditor
                                v-model:content="form.content"
                                content-type="html"
                                theme="snow"
                                :toolbar="[
                                    ['bold', 'italic', 'underline'],
                                    [{ list: 'ordered' }, { list: 'bullet' }],
                                    ['link'],
                                    ['clean'],
                                ]"
                            />
                        </div>
                        <div v-else class="clay-wysiwyg">
                            <div class="min-h-32 px-3 py-2 text-sm text-muted-foreground">
                                Loading editor…
                            </div>
                        </div>
                        <InputError :message="form.errors.content" />
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <Button type="submit" :disabled="form.processing">Save</Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
