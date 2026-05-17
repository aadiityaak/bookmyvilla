<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';
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
const logoDragOver = ref(false);
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

const setLogo = (file: File | null) => {
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

const onLogoChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    if (file && file.type.startsWith('image/')) {
        setLogo(file);
    } else {
        setLogo(null);
    }
    input.value = '';
};

const onLogoDrop = (event: DragEvent) => {
    event.preventDefault();
    logoDragOver.value = false;
    const file = event.dataTransfer?.files?.[0] ?? null;
    if (file && file.type.startsWith('image/')) {
        setLogo(file);
    }
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

const isHexColor = (value: string) => /^#([0-9a-fA-F]{6})$/.test(value.trim());

const primaryColorPicker = computed<string>({
    get() {
        return isHexColor(form.primary_color) ? String(form.primary_color).trim() : '#000000';
    },
    set(value) {
        form.primary_color = value;
    },
});

const secondaryColorPicker = computed<string>({
    get() {
        return isHexColor(form.secondary_color) ? String(form.secondary_color).trim() : '#000000';
    },
    set(value) {
        form.secondary_color = value;
    },
});

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
                    <div class="flex items-center gap-3">
                        <input
                            id="primary_color_picker"
                            v-model="primaryColorPicker"
                            type="color"
                            class="h-11 w-14 shrink-0 cursor-pointer rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-1"
                        />
                        <Input
                            id="primary_color"
                            v-model="form.primary_color"
                            class="clay-control"
                            placeholder="#1a3a3a"
                        />
                    </div>
                    <InputError :message="form.errors.primary_color" />
                </div>

                <div class="grid gap-2">
                    <Label for="secondary_color">Warna sekunder</Label>
                    <div class="flex items-center gap-3">
                        <input
                            id="secondary_color_picker"
                            v-model="secondaryColorPicker"
                            type="color"
                            class="h-11 w-14 shrink-0 cursor-pointer rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-1"
                        />
                        <Input
                            id="secondary_color"
                            v-model="form.secondary_color"
                            class="clay-control"
                            placeholder="#ff4d8b"
                        />
                    </div>
                    <InputError :message="form.errors.secondary_color" />
                </div>
            </div>

            <div class="grid gap-3">
                <div class="flex items-center justify-between gap-3">
                    <Label class="text-sm font-medium text-[color:var(--clay-ink)]">
                        Logo
                    </Label>
                    <div class="flex items-center gap-2">
                        <Button type="button" variant="outline" @click="pickLogo">
                            Browse
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
                    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-soft)] px-4 py-8 text-center"
                    :class="[logoDragOver ? 'border-[color:var(--clay-ink)]' : '']"
                    @click="pickLogo"
                    @dragenter.prevent="logoDragOver = true"
                    @dragover.prevent="logoDragOver = true"
                    @dragleave.prevent="logoDragOver = false"
                    @drop="onLogoDrop"
                >
                    <div
                        v-if="logoPreview"
                        class="w-full overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
                    >
                        <img :src="logoPreview" alt="" class="h-28 w-full object-contain p-4" />
                    </div>
                    <div v-else class="text-sm font-medium text-[color:var(--clay-ink)]">
                        Drag & drop logo di sini
                    </div>
                    <div v-if="!logoPreview" class="text-xs text-[color:var(--clay-muted)]">
                        PNG/JPG sampai 5MB
                    </div>
                </div>

                <InputError :message="form.errors.logo_file" />
            </div>

            <div class="flex items-center justify-end gap-2">
                <Button :disabled="form.processing">Simpan</Button>
            </div>
        </form>
    </div>
</template>
