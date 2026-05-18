<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type BankAccount = {
    bank_name: string;
    account_name: string;
    account_number: string;
};

type PaymentSettings = {
    banks: { bank_name: string | null; account_name: string | null; account_number: string | null }[];
    qris_image_path: string | null;
};

const props = defineProps<{
    settings: PaymentSettings;
}>();

const toImageUrl = (path: string | null) => {
    if (!path) return null;
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    return `/storage/${path}`;
};

const fileInputRef = ref<HTMLInputElement | null>(null);
const qrisDragOver = ref(false);
const qrisPreview = ref<string | null>(toImageUrl(props.settings.qris_image_path));
let qrisObjectUrl: string | null = null;

const emptyBank = (): BankAccount => ({
    bank_name: '',
    account_name: '',
    account_number: '',
});

const form = useForm({
    banks: Array.isArray(props.settings.banks) && props.settings.banks.length > 0
        ? props.settings.banks.map((b) => ({
              bank_name: b.bank_name ?? '',
              account_name: b.account_name ?? '',
              account_number: b.account_number ?? '',
          }))
        : [emptyBank()],
    qris_remove: false,
    qris_file: null as File | null,
});

const addBank = () => {
    (form.banks as unknown as BankAccount[]).push(emptyBank());
};

const removeBank = (idx: number) => {
    const banks = form.banks as unknown as BankAccount[];
    if (banks.length <= 1) {
        banks[0] = emptyBank();
        return;
    }
    banks.splice(idx, 1);
};

const bankError = (idx: number, field: keyof BankAccount) => {
    return (form.errors as any)[`banks.${idx}.${String(field)}`] ?? null;
};

const pickQris = () => {
    fileInputRef.value?.click();
};

const setQris = (file: File | null) => {
    form.qris_file = file;
    form.qris_remove = false;

    if (qrisObjectUrl) {
        URL.revokeObjectURL(qrisObjectUrl);
        qrisObjectUrl = null;
    }

    if (file) {
        qrisObjectUrl = URL.createObjectURL(file);
        qrisPreview.value = qrisObjectUrl;
        return;
    }

    qrisPreview.value = toImageUrl(props.settings.qris_image_path);
};

const onQrisChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    if (file && file.type.startsWith('image/')) {
        setQris(file);
    } else {
        setQris(null);
    }
    input.value = '';
};

const onQrisDrop = (event: DragEvent) => {
    event.preventDefault();
    qrisDragOver.value = false;
    const file = event.dataTransfer?.files?.[0] ?? null;
    if (file && file.type.startsWith('image/')) {
        setQris(file);
    }
};

const removeQris = () => {
    form.qris_remove = true;
    form.qris_file = null;

    if (qrisObjectUrl) {
        URL.revokeObjectURL(qrisObjectUrl);
        qrisObjectUrl = null;
    }

    qrisPreview.value = null;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post('/admin/settings/payment', {
        preserveScroll: true,
        onSuccess: () => {
            form.qris_file = null;
            form.qris_remove = false;
        },
    });
};

onUnmounted(() => {
    if (qrisObjectUrl) URL.revokeObjectURL(qrisObjectUrl);
});

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Setting',
                href: '/admin/settings/branding',
            },
            {
                title: 'Payment',
                href: '/admin/settings/payment',
            },
        ],
    },
});
</script>

<template>
    <Head title="Setting - Payment" />

    <h1 class="sr-only">Setting Payment</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Payment"
            description="Atur rekening transfer dan QRIS untuk pembayaran"
        />

        <form
            class="space-y-6 rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-5"
            @submit.prevent="submit"
        >
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <Label class="text-sm font-medium text-[color:var(--clay-ink)]">
                        Rekening bank
                    </Label>
                    <Button type="button" variant="outline" size="sm" @click="addBank">
                        <Plus />
                        Tambah
                    </Button>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(_, idx) in form.banks"
                        :key="idx"
                        class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-soft)] p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-xs font-medium text-[color:var(--clay-muted)]">
                                    Bank {{ idx + 1 }}
                                </div>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon-sm"
                                class="shrink-0"
                                @click="removeBank(idx)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label :for="`bank_name_${idx}`">Nama bank</Label>
                                <Input
                                    :id="`bank_name_${idx}`"
                                    v-model="form.banks[idx].bank_name"
                                    class="clay-control"
                                    placeholder="Contoh: BCA"
                                />
                                <InputError :message="bankError(idx, 'bank_name')" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`account_number_${idx}`">Nomor rekening</Label>
                                <Input
                                    :id="`account_number_${idx}`"
                                    v-model="form.banks[idx].account_number"
                                    class="clay-control"
                                    placeholder="Contoh: 1234567890"
                                />
                                <InputError :message="bankError(idx, 'account_number')" />
                            </div>
                        </div>

                        <div class="mt-4 grid gap-2">
                            <Label :for="`account_name_${idx}`">Nama pemilik rekening</Label>
                            <Input
                                :id="`account_name_${idx}`"
                                v-model="form.banks[idx].account_name"
                                class="clay-control"
                                placeholder="Contoh: PT BookMyVilla"
                            />
                            <InputError :message="bankError(idx, 'account_name')" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-3">
                <div class="flex items-center justify-between gap-3">
                    <Label class="text-sm font-medium text-[color:var(--clay-ink)]">
                        QRIS
                    </Label>
                    <div class="flex items-center gap-2">
                        <Button type="button" variant="outline" @click="pickQris">
                            Browse
                        </Button>
                        <Button
                            v-if="qrisPreview"
                            type="button"
                            variant="outline"
                            @click="removeQris"
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
                    @change="onQrisChange"
                />

                <div
                    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-soft)] px-4 py-8 text-center"
                    :class="[qrisDragOver ? 'border-[color:var(--clay-ink)]' : '']"
                    @click="pickQris"
                    @dragenter.prevent="qrisDragOver = true"
                    @dragover.prevent="qrisDragOver = true"
                    @dragleave.prevent="qrisDragOver = false"
                    @drop="onQrisDrop"
                >
                    <div
                        v-if="qrisPreview"
                        class="w-full overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
                    >
                        <img :src="qrisPreview" alt="" class="h-44 w-full object-contain p-4" />
                    </div>
                    <div v-else class="text-sm font-medium text-[color:var(--clay-ink)]">
                        Drag & drop QRIS di sini
                    </div>
                    <div v-if="!qrisPreview" class="text-xs text-[color:var(--clay-muted)]">
                        PNG/JPG sampai 5MB
                    </div>
                </div>

                <InputError :message="form.errors.qris_file" />
            </div>

            <div class="flex items-center justify-end gap-2">
                <Button :disabled="form.processing">Simpan</Button>
            </div>
        </form>
    </div>
</template>
