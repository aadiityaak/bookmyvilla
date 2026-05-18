<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

type Article = {
    id: number;
    title: string;
    slug: string;
    featured_image: string | null;
    excerpt: string | null;
    content: string | null;
    published_at: string | null;
    author: { name: string } | null;
};

type LatestArticle = {
    id: number;
    title: string;
    slug: string;
    featured_image: string | null;
    published_at: string | null;
};

const props = defineProps<{
    article: Article;
    latest: LatestArticle[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Artikel',
                href: '/',
            },
        ],
    },
});

const page = usePage();
const branding = computed(() => (page.props as any)?.branding ?? null);
const appName = computed(() => branding.value?.app_name ?? (page.props as any)?.name ?? 'BookMyVilla');

const toImageUrl = (path: string | null): string | null => {
    if (!path) return null;
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    return `/storage/${path}`;
};

const heroImage = computed(() => toImageUrl(props.article.featured_image));

const formatDate = (iso: string | null) => {
    if (!iso) return '';
    const d = new Date(iso);
    if (!Number.isFinite(d.getTime())) return '';
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const authorName = computed(() => props.article.author?.name ?? appName.value);
</script>

<template>
    <Head :title="props.article.title" />

    <div class="min-h-dvh bg-[color:var(--clay-canvas)] text-[color:var(--clay-ink)]">
        <section class="mx-auto w-full max-w-md pb-[calc(7rem+env(safe-area-inset-bottom))]">
            <div class="relative">
                <div class="h-64 w-full overflow-hidden bg-[color:var(--clay-surface-soft)]">
                    <img
                        v-if="heroImage"
                        :src="heroImage"
                        alt=""
                        class="h-full w-full object-cover"
                        loading="lazy"
                    />
                </div>
                <div class="absolute inset-0 bg-gradient-to-b from-black/30 via-black/0 to-[color:var(--clay-canvas)]" />

                <div class="absolute left-4 top-4">
                    <Link
                        href="/"
                        class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-black/20 px-3 py-2 text-xs font-medium text-white backdrop-blur"
                    >
                        <ChevronLeft class="h-4 w-4" />
                        Home
                    </Link>
                </div>
            </div>

            <div class="-mt-10 rounded-t-3xl bg-[color:var(--clay-canvas)] px-4 pt-6">
                <div class="text-xs font-medium tracking-wide text-[color:var(--clay-muted)]">
                    Artikel
                </div>
                <h1 class="mt-2 text-2xl font-semibold leading-tight tracking-tight">
                    {{ props.article.title }}
                </h1>

                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-[color:var(--clay-muted)]">
                    <div class="inline-flex items-center gap-2">
                        <span class="inline-flex h-6 items-center rounded-full bg-[color:var(--clay-surface-soft)] px-2.5">
                            {{ authorName }}
                        </span>
                    </div>
                    <div class="inline-flex items-center gap-2">
                        <span>{{ formatDate(props.article.published_at) }}</span>
                    </div>
                </div>

                <div v-if="props.article.excerpt" class="mt-4 text-sm leading-relaxed text-[color:var(--clay-body)]">
                    {{ props.article.excerpt }}
                </div>

                <div
                    v-if="props.article.content"
                    class="mt-6 text-sm leading-6 text-[color:var(--clay-body)] [&_a]:text-[color:var(--primary)] [&_a]:underline [&_blockquote]:border-l-2 [&_blockquote]:border-[color:var(--clay-hairline)] [&_blockquote]:pl-3 [&_blockquote]:text-[color:var(--clay-muted)] [&_code]:rounded [&_code]:bg-[color:var(--clay-surface-soft)] [&_code]:px-1 [&_h3]:mt-6 [&_h3]:text-base [&_h3]:font-semibold [&_h4]:mt-5 [&_h4]:text-sm [&_h4]:font-semibold [&_li]:mb-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:text-sm [&_ol]:text-[color:var(--clay-body)] [&_p]:mb-3 [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:border [&_pre]:border-[color:var(--clay-hairline)] [&_pre]:bg-[color:var(--clay-surface-soft)] [&_pre]:p-3 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:text-sm [&_ul]:text-[color:var(--clay-body)]"
                    v-html="props.article.content"
                />
            </div>

            <div v-if="props.latest.length" class="mt-8 px-4">
                <div class="text-xs font-medium tracking-wide text-[color:var(--clay-muted)]">
                    Next up
                </div>
                <div class="mt-1 text-base font-semibold">
                    Artikel lainnya
                </div>

                <div class="mt-3 divide-y divide-[color:var(--clay-hairline)]">
                    <Link
                        v-for="a in props.latest"
                        :key="a.id"
                        :href="`/articles/${a.slug}`"
                        class="group flex items-start gap-3 py-3"
                    >
                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-md bg-[color:var(--clay-surface-soft)]">
                            <img
                                v-if="toImageUrl(a.featured_image)"
                                :src="toImageUrl(a.featured_image) ?? ''"
                                alt=""
                                class="h-full w-full object-cover"
                                loading="lazy"
                            />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="line-clamp-2 text-sm font-semibold text-[color:var(--clay-ink)]">
                                {{ a.title }}
                            </div>
                            <div class="mt-1 text-[11px] text-[color:var(--clay-muted)]">
                                {{ formatDate(a.published_at) }}
                            </div>
                        </div>

                        <ChevronRight class="mt-1 h-4 w-4 shrink-0 text-[color:var(--clay-muted)] opacity-70 group-hover:opacity-100" />
                    </Link>
                </div>
            </div>
        </section>
    </div>
</template>
