<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

defineProps<Props>();

const page = usePage();
const user = computed(() => (page.props as any)?.auth?.user ?? null);

const imageUrl = (path: string | null) => {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const avatarPreview = ref<string | null>(imageUrl(user.value?.avatar ?? null));
const fileInputRef = ref<HTMLInputElement | null>(null);

const form = useForm({
    name: user.value?.name ?? '',
    email: user.value?.email ?? '',
    phone: user.value?.phone ?? '',
    address: user.value?.address ?? '',
    avatar_file: null as File | null,
});

const onPickAvatar = () => {
    fileInputRef.value?.click();
};

const onAvatarChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.avatar_file = file;
    avatarPreview.value = file ? URL.createObjectURL(file) : imageUrl(user.value?.avatar ?? null);
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post('/profile', {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Profil" />

    <div class="mx-auto flex w-full max-w-md flex-col gap-6 px-4 py-6">
        <div class="flex items-start justify-between gap-3">
            <Heading
                variant="small"
                title="Profil"
                description="Edit foto dan data diri"
            />
            <Button size="sm" variant="outline" as-child>
                <Link href="/dashboard">Selesai</Link>
            </Button>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-4">
                <div class="flex items-center gap-4">
                    <div
                        class="h-16 w-16 overflow-hidden rounded-full border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)]"
                    >
                        <img v-if="avatarPreview" :src="avatarPreview" alt="" class="h-full w-full object-cover" />
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-medium text-[color:var(--clay-ink)]">Foto profil</div>
                        <div class="mt-1 text-sm text-[color:var(--clay-muted)]">
                            JPG/PNG maks 5MB
                        </div>
                        <div class="mt-3 flex gap-2">
                            <Button type="button" size="sm" variant="outline" @click="onPickAvatar">
                                Ganti foto
                            </Button>
                        </div>
                    </div>
                </div>
                <input
                    ref="fileInputRef"
                    class="hidden"
                    type="file"
                    accept="image/*"
                    @change="onAvatarChange"
                />
                <InputError class="mt-2" :message="form.errors.avatar_file" />
            </div>

            <div class="grid gap-2">
                <Label for="name">Nama</Label>
                <Input id="name" v-model="form.name" class="clay-control" />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" type="email" class="clay-control" />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">No. HP</Label>
                <Input id="phone" v-model="form.phone" class="clay-control" placeholder="08xxxxxxxxxx" />
                <InputError :message="form.errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="address">Alamat</Label>
                <textarea
                    id="address"
                    v-model="form.address"
                    class="clay-textarea"
                    rows="4"
                    placeholder="Alamat lengkap"
                />
                <InputError :message="form.errors.address" />
            </div>

            <div class="flex items-center gap-3">
                <Button class="w-full" type="submit" :disabled="form.processing">
                    Simpan
                </Button>
            </div>
        </form>
    </div>
</template>
