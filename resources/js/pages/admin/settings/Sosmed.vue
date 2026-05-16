<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type SosmedSettings = {
    website: string | null;
    email: string | null;
    phone: string | null;
    whatsapp: string | null;
    instagram: string | null;
    facebook: string | null;
    tiktok: string | null;
    youtube: string | null;
    twitter: string | null;
};

const props = defineProps<{
    settings: SosmedSettings;
}>();

const form = useForm({
    website: props.settings.website ?? '',
    email: props.settings.email ?? '',
    phone: props.settings.phone ?? '',
    whatsapp: props.settings.whatsapp ?? '',
    instagram: props.settings.instagram ?? '',
    facebook: props.settings.facebook ?? '',
    tiktok: props.settings.tiktok ?? '',
    youtube: props.settings.youtube ?? '',
    twitter: props.settings.twitter ?? '',
});

const submit = () => {
    form.patch('/admin/settings/sosmed', {
        preserveScroll: true,
    });
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Setting',
                href: '/admin/settings/branding',
            },
            {
                title: 'Sosmed',
                href: '/admin/settings/sosmed',
            },
        ],
    },
});
</script>

<template>
    <Head title="Setting - Sosmed" />

    <h1 class="sr-only">Setting Sosmed</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Sosmed"
            description="Atur tautan sosial media & kontak yang tampil di aplikasi"
        />

        <form
            class="space-y-6 rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-5"
            @submit.prevent="submit"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="website">Website</Label>
                    <Input id="website" v-model="form.website" class="clay-control" placeholder="https://..." />
                    <InputError :message="form.errors.website" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input id="email" v-model="form.email" class="clay-control" placeholder="email@domain.com" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone">Telepon</Label>
                    <Input id="phone" v-model="form.phone" class="clay-control" placeholder="+62..." />
                    <InputError :message="form.errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="whatsapp">WhatsApp</Label>
                    <Input id="whatsapp" v-model="form.whatsapp" class="clay-control" placeholder="+62..." />
                    <InputError :message="form.errors.whatsapp" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="instagram">Instagram</Label>
                    <Input id="instagram" v-model="form.instagram" class="clay-control" placeholder="https://instagram.com/..." />
                    <InputError :message="form.errors.instagram" />
                </div>

                <div class="grid gap-2">
                    <Label for="facebook">Facebook</Label>
                    <Input id="facebook" v-model="form.facebook" class="clay-control" placeholder="https://facebook.com/..." />
                    <InputError :message="form.errors.facebook" />
                </div>

                <div class="grid gap-2">
                    <Label for="tiktok">TikTok</Label>
                    <Input id="tiktok" v-model="form.tiktok" class="clay-control" placeholder="https://tiktok.com/@..." />
                    <InputError :message="form.errors.tiktok" />
                </div>

                <div class="grid gap-2">
                    <Label for="youtube">YouTube</Label>
                    <Input id="youtube" v-model="form.youtube" class="clay-control" placeholder="https://youtube.com/@..." />
                    <InputError :message="form.errors.youtube" />
                </div>

                <div class="grid gap-2">
                    <Label for="twitter">X / Twitter</Label>
                    <Input id="twitter" v-model="form.twitter" class="clay-control" placeholder="https://x.com/..." />
                    <InputError :message="form.errors.twitter" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <Button :disabled="form.processing">Simpan</Button>
            </div>
        </form>
    </div>
</template>
