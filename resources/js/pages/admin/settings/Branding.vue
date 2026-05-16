<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { onUnmounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type BrandingSettings = {
    app_name: string;
    tagline: string | null;
    primary_color: string | null;
    secondary_color: string | null;
    logo_path: string | null;
};

const props = defineProps<{
    settings: BrandingSettings;
}>();

const toImageUrl = (path: string | null) => {
    if (!path) return null;
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    return `/storage/${path}`;
};

const fileInputRef = ref<HTMLInputElement | null>(null);
const logoPreview = ref<string | null>(toImageUrl(props.settings.logo_path));
let logoObjectUrl: string | null = null;

const form = useForm({
    app_name: props.settings.app_name ?? '',
    tagline: props.settings.tagline ?? '',
    primary_color: props.settings.primary_color ?? '',
    secondary_color: props.settings.secondary_color ?? '',
    logo_remove: false,
    logo_file: null as File | null,
});

const pickLogo = () => {
    fileInputRef.value?.click();
};

const onLogoChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.logo_file = file;
    form.logo_remove = false;

    if (logoObjectUrl) {
        URL.revokeObjectURL(logoObjectUrl);
        logoObjectUrl = null;
    }

    if (file) {
        logoObjectUrl = URL.createObjectURL(file);
        logoPreview.value = logoObjectUrl;
        return;
    }

    logoPreview.value = toImageUrl(props.settings.logo_path);
};

const removeLogo = () => {
    form.logo_remove = true;
    form.logo_file = null;

    if (logoObjectUrl) {
        URL.revokeObjectURL(logoObjectUrl);
        logoObjectUrl = null;
    }

    logoPreview.value = null;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post('/admin/settings/branding', {
        preserveScroll: true,
        onSuccess: () => {
            form.logo_file = null;
            form.logo_remove = false;
        },
    });
};

onUnmounted(() => {
    if (logoObjectUrl) URL.revokeObjectURL(logoObjectUrl);
});

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Setting',
                href: '/admin/settings/branding',
            },
            {
                title: 'Branding',
                href: '/admin/settings/branding',
            },
        ],
    },
});
</script>

<template>
    <Head title="Setting - Branding" />

    <h1 class="sr-only">Setting Branding</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Branding"
            description="Atur identitas aplikasi: nama, logo, dan warna"
        />

        <form
            class="space-y-6 rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-5"
            @submit.prevent="submit"
        >
            <div class="grid gap-2">
                <Label for="app_name">Nama aplikasi</Label>
                <Input id="app_name" v-model="form.app_name" class="clay-control" />
                <InputError :message="form.errors.app_name" />
            </div>

            <div class="grid gap-2">
                <Label for="tagline">Tagline</Label>
                <Input
                    id="tagline"
                    v-model="form.tagline"
                    class="clay-control"
                    placeholder="Contoh: Booking villa harian, tanpa ribet"
                />
                <InputError :message="form.errors.tagline" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="primary_color">Warna utama</Label>
                    <Input
                        id="primary_color"
                        v-model="form.primary_color"
                        class="clay-control"
                        placeholder="#1a3a3a"
                    />
                    <InputError :message="form.errors.primary_color" />
                </div>

                <div class="grid gap-2">
                    <Label for="secondary_color">Warna sekunder</Label>
                    <Input
                        id="secondary_color"
                        v-model="form.secondary_color"
                        class="clay-control"
                        placeholder="#ff4d8b"
                    />
                    <InputError :message="form.errors.secondary_color" />
                </div>
            </div>

            <div class="grid gap-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm font-medium text-[color:var(--clay-ink)]">
                        Logo
                    </div>
                    <div class="flex items-center gap-2">
                        <Button type="button" variant="outline" @click="pickLogo">
                            Upload logo
                        </Button>
                        <Button
                            v-if="logoPreview"
                            type="button"
                            variant="outline"
                            @click="removeLogo"
                        >
                            Hapus
                        </Button>
                    </div>
                </div>

                <input
                    ref="fileInputRef"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="onLogoChange"
                />

                <div
                    v-if="logoPreview"
                    class="overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)]"
                >
                    <img :src="logoPreview" alt="" class="h-28 w-full object-contain p-4" />
                </div>

                <div v-else class="text-sm text-[color:var(--clay-muted)]">
                    Belum ada logo.
                </div>

                <InputError :message="form.errors.logo_file" />
            </div>

            <div class="flex items-center justify-end gap-2">
                <Button :disabled="form.processing">Simpan</Button>
            </div>
        </form>
    </div>
</template>
