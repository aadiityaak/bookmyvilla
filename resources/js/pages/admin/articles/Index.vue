<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type ArticleRow = {
    id: number;
    title: string;
    slug: string;
    featured_image: string | null;
    status: string;
    published_at: string | null;
    created_at: string | null;
    updated_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type ArticlesPaginator = {
    data: ArticleRow[];
    links: PaginationLink[];
    total: number;
};

const props = defineProps<{
    filters: {
        q?: string;
        status?: string;
    };
    articles: ArticlesPaginator;
    statuses: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Articles',
                href: '/admin/articles',
            },
        ],
    },
});

const form = useForm({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
});

const hasFilters = computed(() => Boolean(form.q || form.status));

const applyFilters = () => {
    router.get('/admin/articles', form.data(), {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    form.q = '';
    form.status = '';
    applyFilters();
};

const imageUrl = (path: string | null) => {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const statusPillClass = (status: string) => {
    switch (status) {
        case 'published':
            return 'bg-[color:var(--clay-surface-soft)] text-[color:var(--clay-ink)] border-transparent';
        case 'archived':
            return 'bg-[color:var(--clay-surface-strong)] text-[color:var(--clay-muted)] border-transparent';
        case 'draft':
        default:
            return 'bg-[color:var(--clay-brand-lavender)] text-black border-transparent';
    }
};
</script>

<template>
    <Head title="Articles" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 py-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <Heading
                variant="default"
                title="Articles"
                description="Kelola blog/artikel"
            />

            <div class="flex items-center gap-2">
                <Badge
                    variant="outline"
                    class="bg-[color:var(--clay-surface-card)] text-[color:var(--clay-body)]"
                >
                    {{ articles.total }} total
                </Badge>
                <Button as-child>
                    <Link href="/admin/articles/create">Create article</Link>
                </Button>
            </div>
        </div>

        <div class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                <div class="md:col-span-9">
                    <Label for="q">Search</Label>
                    <Input
                        id="q"
                        v-model="form.q"
                        class="clay-control"
                        placeholder="Title or slug"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="md:col-span-3">
                    <Label for="status">Status</Label>
                    <select
                        id="status"
                        v-model="form.status"
                        class="clay-select"
                        @change="applyFilters"
                    >
                        <option value="">All</option>
                        <option v-for="status in statuses" :key="status" :value="status">
                            {{ status }}
                        </option>
                    </select>
                </div>
            </div>

            <div v-if="hasFilters" class="mt-4 flex items-center justify-end gap-2">
                <Button variant="outline" @click="clearFilters"> Clear </Button>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]">
            <div
                class="grid grid-cols-12 gap-3 border-b border-[color:var(--clay-hairline)] px-5 py-3 text-xs font-medium uppercase tracking-wide text-[color:var(--clay-muted)]"
            >
                <div class="col-span-7">Article</div>
                <div class="col-span-3">Status</div>
                <div class="col-span-2 text-right">Action</div>
            </div>

            <div
                v-for="article in articles.data"
                :key="article.id"
                class="grid grid-cols-12 gap-3 border-b border-[color:var(--clay-hairline)] px-5 py-4 text-sm last:border-b-0 hover:bg-[color:var(--clay-surface-soft)]"
            >
                <div class="col-span-7 flex min-w-0 items-start gap-3">
                    <div class="h-12 w-16 shrink-0 overflow-hidden rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)]">
                        <img
                            v-if="imageUrl(article.featured_image)"
                            :src="imageUrl(article.featured_image) ?? ''"
                            alt=""
                            class="h-full w-full object-cover"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium text-[color:var(--clay-ink)]">
                            {{ article.title }}
                        </div>
                        <div class="truncate text-xs text-[color:var(--clay-muted)]">
                            /{{ article.slug }}
                        </div>
                    </div>
                </div>

                <div class="col-span-3 flex items-center gap-2">
                    <Badge :class="statusPillClass(article.status)">
                        {{ article.status }}
                    </Badge>
                    <Badge
                        v-if="article.status === 'published' && article.published_at"
                        variant="outline"
                        class="bg-[color:var(--clay-surface-card)] text-[color:var(--clay-muted)]"
                    >
                        published
                    </Badge>
                </div>

                <div class="col-span-2 flex justify-end">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="`/admin/articles/${article.id}/edit`">Edit</Link>
                    </Button>
                </div>
            </div>

            <div v-if="articles.data.length === 0" class="px-5 py-10 text-sm text-[color:var(--clay-muted)]">
                No articles found.
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Link
                v-for="link in articles.links"
                :key="link.label"
                :href="link.url ?? ''"
                class="rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] px-3 py-1.5 text-sm"
                :class="[
                    link.active
                        ? 'bg-[color:var(--clay-surface-card)] text-[color:var(--clay-ink)]'
                        : 'text-[color:var(--clay-body)]',
                    !link.url ? 'pointer-events-none opacity-50' : '',
                ]"
            >
                <span v-html="link.label" />
            </Link>
        </div>
    </div>
</template>
