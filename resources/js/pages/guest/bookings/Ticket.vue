<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

type TicketBooking = {
    id: number;
    status: string;
    check_in_date: string;
    check_out_date: string;
    guests_count: number;
    guest_name: string | null;
    created_at: string | null;
    property: { id: number; name: string; featured_image: string | null; address: string | null } | null;
};

const props = defineProps<{
    booking: TicketBooking;
    ticket_payload: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Bookings',
                href: '/bookings',
            },
            {
                title: 'Ticket',
                href: '#',
            },
        ],
    },
});

const qrDataUrl = ref<string | null>(null);
const qrError = ref<string | null>(null);

const printTicket = () => {
    window.print();
};

onMounted(async () => {
    try {
        const QRCode = (await import('qrcode')).default;
        qrDataUrl.value = await QRCode.toDataURL(props.ticket_payload, {
            errorCorrectionLevel: 'M',
            margin: 2,
            scale: 8,
            color: {
                dark: getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#0a0a0a',
                light: '#ffffff',
            },
        });
    } catch (e: any) {
        qrError.value = e?.message ? String(e.message) : 'Gagal generate QR';
    }
});
</script>

<template>
    <Head :title="`Ticket #${booking.id}`" />

    <div class="mx-auto flex w-full max-w-md flex-col gap-6 px-4 py-6">
        <Heading
            variant="small"
            title="Barcode Check-in"
            description="Tunjukkan barcode ini ke resepsionis/pengurus saat check-in"
        />

        <div class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-4">
            <div class="flex items-center justify-between gap-3">
                <div class="text-sm font-semibold text-[color:var(--clay-ink)]">
                    Booking #{{ booking.id }}
                </div>
                <div class="rounded-md border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] px-2 py-1 text-xs text-[color:var(--clay-ink)]">
                    {{ booking.status }}
                </div>
            </div>

            <div class="mt-3 text-sm text-[color:var(--clay-body)]">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-[color:var(--clay-muted)]">Properti</div>
                    <div class="text-right font-medium text-[color:var(--clay-ink)]">
                        {{ booking.property?.name ?? '-' }}
                    </div>
                </div>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <div class="text-[color:var(--clay-muted)]">Tamu</div>
                    <div class="text-right">{{ booking.guests_count }}</div>
                </div>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <div class="text-[color:var(--clay-muted)]">Tanggal</div>
                    <div class="text-right">
                        {{ booking.check_in_date }} → {{ booking.check_out_date }}
                    </div>
                </div>
                <div v-if="booking.guest_name" class="mt-1 flex items-center justify-between gap-3">
                    <div class="text-[color:var(--clay-muted)]">Nama</div>
                    <div class="text-right">{{ booking.guest_name }}</div>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-[color:var(--clay-hairline)] bg-white p-5">
            <div v-if="qrDataUrl" class="flex flex-col items-center gap-3">
                <img :src="qrDataUrl" alt="QR Ticket" class="h-60 w-60" />
                <div class="text-xs text-[color:var(--clay-muted)]">
                    Scan untuk melihat detail (Booking #{{ booking.id }})
                </div>
            </div>
            <div v-else class="text-sm text-[color:var(--clay-muted)]">
                {{ qrError ?? 'Membuat barcode…' }}
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <Button variant="outline" as-child>
                <Link href="/bookings">Kembali</Link>
            </Button>
            <Button type="button" @click="printTicket">Cetak</Button>
        </div>
    </div>
</template>
