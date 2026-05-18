<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onUnmounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';

type BankAccount = {
    bank_name: string | null;
    account_name: string | null;
    account_number: string | null;
};

type BookingDetail = {
    id: number;
    status: string;
    check_in_date: string;
    check_out_date: string;
    guests_count: number;
    payment_proof_url?: string | null;
    payment_proof_uploaded_at?: string | null;
    created_at: string | null;
    property: { id: number; name: string; featured_image: string | null; address: string | null } | null;
};

const props = defineProps<{
    booking: BookingDetail;
    payment: {
        banks: BankAccount[];
        qris_image_url: string | null;
    };
}>();

const fileInputRef = ref<HTMLInputElement | null>(null);
const proofDragOver = ref(false);
const proofPreview = ref<string | null>(props.booking.payment_proof_url ?? null);
let proofObjectUrl: string | null = null;

const form = useForm({
    payment_proof_file: null as File | null,
});

const pickProof = () => {
    fileInputRef.value?.click();
};

const setProof = (file: File | null) => {
    form.payment_proof_file = file;

    if (proofObjectUrl) {
        URL.revokeObjectURL(proofObjectUrl);
        proofObjectUrl = null;
    }

    if (file) {
        proofObjectUrl = URL.createObjectURL(file);
        proofPreview.value = proofObjectUrl;
        return;
    }

    proofPreview.value = props.booking.payment_proof_url ?? null;
};

const onProofChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    if (file && file.type.startsWith('image/')) {
        setProof(file);
    } else {
        setProof(null);
    }
    input.value = '';
};

const onProofDrop = (event: DragEvent) => {
    event.preventDefault();
    proofDragOver.value = false;
    const file = event.dataTransfer?.files?.[0] ?? null;
    if (file && file.type.startsWith('image/')) {
        setProof(file);
    }
};

const clearProof = () => {
    setProof(null);
};

const submitProof = () => {
    form.post(`/bookings/${props.booking.id}/payment`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset('payment_proof_file');
        },
    });
};

onUnmounted(() => {
    if (proofObjectUrl) URL.revokeObjectURL(proofObjectUrl);
});

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Bookings',
                href: '/bookings',
            },
            {
                title: 'Payment',
                href: '#',
            },
        ],
    },
});

const imageUrl = (path: string | null) => {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
};
</script>

<template>
    <Head title="Payment" />

    <div class="mx-auto flex w-full max-w-md flex-col gap-6 px-4 py-6">
        <Heading
            variant="small"
            title="Payment"
            description="Selesaikan pembayaran booking kamu"
        />

        <div class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-4">
            <div class="flex items-start gap-3">
                <div
                    class="h-12 w-16 shrink-0 overflow-hidden rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)]"
                >
                    <img
                        v-if="booking.property?.featured_image && imageUrl(booking.property.featured_image)"
                        :src="imageUrl(booking.property.featured_image) ?? ''"
                        alt=""
                        class="h-full w-full object-cover"
                    />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="truncate font-medium text-[color:var(--clay-ink)]">
                        {{ booking.property?.name ?? '-' }}
                    </div>
                    <div class="mt-1 text-xs text-[color:var(--clay-muted)]">
                        Booking #{{ booking.id }} • {{ booking.status }}
                    </div>
                    <div class="mt-3 space-y-1 text-sm text-[color:var(--clay-body)]">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-[color:var(--clay-muted)]">Tanggal</div>
                            <div class="text-right">
                                {{ booking.check_in_date }} → {{ booking.check_out_date }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-[color:var(--clay-muted)]">Tamu</div>
                            <div class="text-right">{{ booking.guests_count }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <div class="text-sm font-semibold">Transfer Bank</div>

            <div
                v-if="payment.banks.length"
                class="space-y-3"
            >
                <div
                    v-for="(b, idx) in payment.banks"
                    :key="idx"
                    class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-4"
                >
                    <div class="text-xs font-medium text-[color:var(--clay-muted)]">
                        {{ b.bank_name ?? 'Bank' }}
                    </div>
                    <div class="mt-2 space-y-1 text-sm text-[color:var(--clay-body)]">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-[color:var(--clay-muted)]">No. Rek</div>
                            <div class="text-right font-medium text-[color:var(--clay-ink)]">
                                {{ b.account_number ?? '-' }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-[color:var(--clay-muted)]">a.n.</div>
                            <div class="text-right">
                                {{ b.account_name ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="rounded-lg border border-dashed border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-4 text-sm text-[color:var(--clay-muted)]"
            >
                Data rekening belum diatur oleh admin.
            </div>
        </div>

        <div class="space-y-3">
            <div class="text-sm font-semibold">QRIS</div>

            <div
                v-if="payment.qris_image_url"
                class="overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
            >
                <img
                    :src="payment.qris_image_url"
                    alt="QRIS"
                    class="w-full object-contain p-4"
                />
            </div>
            <div
                v-else
                class="rounded-lg border border-dashed border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-4 text-sm text-[color:var(--clay-muted)]"
            >
                QRIS belum diatur oleh admin.
            </div>
        </div>

        <div class="space-y-3">
            <div class="text-sm font-semibold">Konfirmasi Pembayaran</div>

            <div class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-4">
                <div class="text-sm text-[color:var(--clay-body)]">
                    Upload bukti transfer (screenshot / foto).
                </div>

                <div v-if="booking.payment_proof_uploaded_at" class="mt-2 text-xs text-[color:var(--clay-muted)]">
                    Bukti terakhir: {{ booking.payment_proof_uploaded_at }}
                </div>

                <input
                    ref="fileInputRef"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="onProofChange"
                />

                <div
                    class="mt-4 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-soft)] px-4 py-8 text-center"
                    :class="[proofDragOver ? 'border-[color:var(--primary)]' : '']"
                    @click="pickProof"
                    @dragenter.prevent="proofDragOver = true"
                    @dragover.prevent="proofDragOver = true"
                    @dragleave.prevent="proofDragOver = false"
                    @drop="onProofDrop"
                >
                    <div
                        v-if="proofPreview"
                        class="w-full overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
                    >
                        <img :src="proofPreview" alt="Bukti pembayaran" class="w-full object-contain p-4" />
                    </div>
                    <div v-else class="text-sm font-medium text-[color:var(--clay-ink)]">
                        Drag & drop bukti pembayaran di sini
                    </div>
                    <div v-if="!proofPreview" class="text-xs text-[color:var(--clay-muted)]">
                        PNG/JPG sampai 5MB
                    </div>
                </div>

                <InputError :message="form.errors.payment_proof_file" />

                <div class="mt-4 flex items-center justify-end gap-2">
                    <Button type="button" variant="outline" :disabled="form.processing" @click="clearProof">
                        Reset
                    </Button>
                    <Button
                        type="button"
                        :disabled="form.processing || !form.payment_proof_file"
                        @click="submitProof"
                    >
                        Kirim bukti
                    </Button>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <Button variant="outline" as-child>
                <Link href="/bookings">Kembali</Link>
            </Button>
        </div>
    </div>
</template>
