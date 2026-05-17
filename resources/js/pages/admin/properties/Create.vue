<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { QuillEditor } from '@vueup/vue-quill';

const props = defineProps<{
    types: string[];
    statuses: string[];
    canManageAll: boolean;
    owners: { id: number; name: string; email: string; role: string }[];
    investors: { id: number; name: string; email: string; role: string }[];
    provinces: { id: string; name: string }[];
    regencies: { id: string; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Properties',
                href: '/admin/properties',
            },
            {
                title: 'Create',
                href: '/admin/properties/create',
            },
        ],
    },
});

const page = usePage();
const userId = computed(() => (page.props.auth?.user as any)?.id);
const isClient = ref(false);
const ownerQuery = ref('');
const investorQuery = ref('');
const regencies = ref<{ id: string; name: string }[]>(props.regencies ?? []);
const regenciesLoading = ref(false);
const mapEl = ref<HTMLDivElement | null>(null);
let map: any = null;
let marker: any = null;

const parseNumber = (value: unknown) => {
    const n = typeof value === 'number' ? value : Number(String(value ?? '').trim());
    return Number.isFinite(n) ? n : null;
};

const isValidLatLng = (lat: number | null, lng: number | null) => {
    if (lat === null || lng === null) return false;
    return lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
};

const setCoordinates = (lat: number, lng: number, options?: { moveMap?: boolean }) => {
    const safeLat = Math.max(-90, Math.min(90, lat));
    const safeLng = Math.max(-180, Math.min(180, lng));

    form.latitude = Number.isFinite(safeLat) ? safeLat.toFixed(7) : '';
    form.longitude = Number.isFinite(safeLng) ? safeLng.toFixed(7) : '';

    if (map && marker) {
        marker.setLatLng([safeLat, safeLng]);
    } else if (map && !marker && (window as any).__leaflet) {
        const L = (window as any).__leaflet;
        marker = L.marker([safeLat, safeLng], { draggable: true }).addTo(map);
        marker.on('dragend', () => {
            const pos = marker.getLatLng();
            setCoordinates(pos.lat, pos.lng);
        });
    }

    if (map && options?.moveMap !== false) {
        map.setView([safeLat, safeLng], Math.max(map.getZoom?.() ?? 13, 13), { animate: true });
    }
};

const clearCoordinates = () => {
    form.latitude = '';
    form.longitude = '';
    if (marker && map) {
        map.removeLayer(marker);
    }
    marker = null;
};

const initMap = async () => {
    if (typeof window === 'undefined') return;
    if (!mapEl.value) return;

    const leafletModule = await import('leaflet');
    const L: any = (leafletModule as any).default ?? leafletModule;
    (window as any).__leaflet = L;
    await import('leaflet/dist/leaflet.css');

    const markerIcon2x = (await import('leaflet/dist/images/marker-icon-2x.png')).default;
    const markerIcon = (await import('leaflet/dist/images/marker-icon.png')).default;
    const markerShadow = (await import('leaflet/dist/images/marker-shadow.png')).default;
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: markerIcon2x,
        iconUrl: markerIcon,
        shadowUrl: markerShadow,
    });

    const lat = parseNumber(form.latitude);
    const lng = parseNumber(form.longitude);
    const hasInitial = isValidLatLng(lat, lng);
    const center = hasInitial && lat !== null && lng !== null ? [lat, lng] : [-8.670458, 115.212629];
    const zoom = hasInitial ? 14 : 10;

    map = L.map(mapEl.value, {
        zoomControl: true,
    }).setView(center, zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    map.on('click', (e: any) => {
        setCoordinates(e.latlng.lat, e.latlng.lng);
    });

    if (hasInitial && lat !== null && lng !== null) {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', () => {
            const pos = marker.getLatLng();
            setCoordinates(pos.lat, pos.lng);
        });
    }
};

const filteredOwners = computed(() => {
    const q = ownerQuery.value.trim().toLowerCase();
    if (!q) return props.owners;
    return props.owners.filter((u) => {
        return (
            u.name.toLowerCase().includes(q) ||
            u.email.toLowerCase().includes(q) ||
            u.role.toLowerCase().includes(q)
        );
    });
});

const filteredInvestors = computed(() => {
    const q = investorQuery.value.trim().toLowerCase();
    if (!q) return props.investors;
    return props.investors.filter((u) => {
        return (
            u.name.toLowerCase().includes(q) ||
            u.email.toLowerCase().includes(q) ||
            u.role.toLowerCase().includes(q)
        );
    });
});

const ownerSelectValue = computed<string>({
    get() {
        return String(form.owner_id ?? '');
    },
    set(value) {
        form.owner_id = Number(value);
    },
});

const investorSelectValue = computed<string>({
    get() {
        return form.investor_id ? String(form.investor_id) : '__none__';
    },
    set(value) {
        form.investor_id = value === '__none__' ? null : Number(value);
    },
});

const form = useForm({
    owner_id: userId.value,
    investor_id: null as number | null,
    type: 'villa',
    name: '',
    featured_image_file: null as File | null,
    address: '',
    province_id: '',
    regency_id: '',
    latitude: '',
    longitude: '',
    description: '',
    amenities: '',
    status: 'draft',
    gallery_files: [] as File[],
});

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

const clearFeaturedImage = () => {
    setFeaturedImage(null);
};

const galleryInputRef = ref<HTMLInputElement | null>(null);
const galleryDragOver = ref(false);
const galleryPreviews = ref<{ file: File; url: string }[]>([]);

const addGalleryFiles = (files: File[]) => {
    const imageFiles = files.filter((file) => file.type.startsWith('image/'));
    if (!imageFiles.length) return;

    form.gallery_files = [...form.gallery_files, ...imageFiles];
    galleryPreviews.value = [
        ...galleryPreviews.value,
        ...imageFiles.map((file) => ({ file, url: URL.createObjectURL(file) })),
    ];
};

const onGalleryFilesSelected = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const files = input.files ? Array.from(input.files) : [];
    if (!files.length) return;
    addGalleryFiles(files);
    input.value = '';
};

const onGalleryDrop = (event: DragEvent) => {
    event.preventDefault();
    galleryDragOver.value = false;
    const files = event.dataTransfer?.files ? Array.from(event.dataTransfer.files) : [];
    if (!files.length) return;
    addGalleryFiles(files);
};

const removeGalleryFile = (index: number) => {
    const preview = galleryPreviews.value[index];
    if (preview) {
        URL.revokeObjectURL(preview.url);
        galleryPreviews.value.splice(index, 1);
    }
    form.gallery_files.splice(index, 1);
};

onMounted(() => {
    isClient.value = true;
    nextTick().then(initMap);
});

const loadRegencies = async (provinceId: string) => {
    if (!provinceId) {
        regencies.value = [];
        return;
    }

    regenciesLoading.value = true;
    try {
        const res = await fetch(`/wilayah/regencies?province_id=${encodeURIComponent(provinceId)}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        const data = (await res.json()) as { id: string; name: string }[];
        regencies.value = Array.isArray(data) ? data : [];
    } finally {
        regenciesLoading.value = false;
    }
};

const onProvinceChange = async () => {
    const provinceId = String(form.province_id ?? '');
    form.regency_id = '';
    await loadRegencies(provinceId);
};

onBeforeUnmount(() => {
    if (map) {
        map.remove();
    }
    map = null;
    marker = null;

    if (featuredImagePreviewUrl.value) {
        URL.revokeObjectURL(featuredImagePreviewUrl.value);
    }
    for (const item of galleryPreviews.value) {
        URL.revokeObjectURL(item.url);
    }
});

watch(
    () => [form.latitude, form.longitude],
    ([nextLat, nextLng]) => {
        if (!map) return;
        const lat = parseNumber(nextLat);
        const lng = parseNumber(nextLng);
        if (lat === null || lng === null) return;
        if (!isValidLatLng(lat, lng)) return;
        setCoordinates(lat, lng, { moveMap: false });
    },
);

const submit = () => {
    form.post('/admin/properties', {
        preserveScroll: true,
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Create property" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 py-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <Heading
                variant="small"
                title="Create property"
                description="Add a new kost or villa (admin)"
            />

            <Button variant="outline" as-child>
                <Link href="/admin/properties">Back</Link>
            </Button>
        </div>

        <div class="grid gap-6 md:grid-cols-12">
            <form
                class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)] p-6 md:col-span-8 md:col-start-3"
                @submit.prevent="submit"
            >
                <div class="grid gap-6">
                    <div v-if="canManageAll" class="grid gap-2">
                        <Label for="owner_id">User</Label>
                        <Select v-model="ownerSelectValue">
                            <SelectTrigger class="clay-select w-full">
                                <SelectValue placeholder="Select user" />
                            </SelectTrigger>
                            <SelectContent
                                v-if="isClient"
                                class="w-(--reka-select-trigger-width)"
                            >
                                <div class="p-2">
                                    <Input
                                        v-model="ownerQuery"
                                        class="clay-control h-9"
                                        placeholder="Search user…"
                                    />
                                </div>
                                <template v-if="filteredOwners.length">
                                    <SelectItem
                                        v-for="u in filteredOwners"
                                        :key="u.id"
                                        :value="String(u.id)"
                                    >
                                        {{ u.name }} ({{ u.email }}) — {{ u.role }}
                                    </SelectItem>
                                </template>
                                <div
                                    v-else
                                    class="px-3 py-2 text-sm text-muted-foreground"
                                >
                                    No results
                                </div>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.owner_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="type">Type</Label>
                        <select
                            id="type"
                            v-model="form.type"
                            class="clay-select"
                        >
                            <option v-for="t in types" :key="t" :value="t">
                                {{ t }}
                            </option>
                        </select>
                        <InputError :message="form.errors.type" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            class="clay-control"
                            placeholder="Property name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Featured image</Label>
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
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="absolute top-2 right-2 bg-[color:var(--clay-canvas)]"
                                @click="clearFeaturedImage"
                            >
                                Remove
                            </Button>
                        </div>

                        <InputError :message="form.errors.featured_image_file" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="address">Address</Label>
                        <Input
                            id="address"
                            v-model="form.address"
                            class="clay-control"
                            placeholder="Address (optional)"
                        />
                        <InputError :message="form.errors.address" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="province_id">Provinsi</Label>
                        <select
                            id="province_id"
                            v-model="form.province_id"
                            class="clay-select"
                            @change="onProvinceChange"
                        >
                            <option value="">Pilih provinsi</option>
                            <option v-for="p in props.provinces" :key="p.id" :value="p.id">
                                {{ p.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.province_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="regency_id">Kota/Kabupaten</Label>
                        <select
                            id="regency_id"
                            v-model="form.regency_id"
                            class="clay-select"
                            :disabled="!form.province_id || regenciesLoading"
                        >
                            <option value="">
                                {{ form.province_id ? 'Pilih kota/kabupaten' : 'Pilih provinsi dulu' }}
                            </option>
                            <option v-for="r in regencies" :key="r.id" :value="r.id">
                                {{ r.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.regency_id" />
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <Label>Koordinat</Label>
                            <Button type="button" size="sm" variant="outline" @click="clearCoordinates">
                                Reset
                            </Button>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="latitude">Latitude</Label>
                                <Input
                                    id="latitude"
                                    v-model="form.latitude"
                                    type="number"
                                    step="0.0000001"
                                    inputmode="decimal"
                                    class="clay-control"
                                    placeholder="-8.6704580"
                                />
                                <InputError :message="form.errors.latitude" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="longitude">Longitude</Label>
                                <Input
                                    id="longitude"
                                    v-model="form.longitude"
                                    type="number"
                                    step="0.0000001"
                                    inputmode="decimal"
                                    class="clay-control"
                                    placeholder="115.2126290"
                                />
                                <InputError :message="form.errors.longitude" />
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]">
                            <div ref="mapEl" class="h-72 w-full" />
                        </div>

                        <div class="text-xs text-[color:var(--clay-muted)]">
                            Klik pada peta untuk memilih titik. Marker bisa di-drag untuk penyesuaian.
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="investor_id">Investor</Label>
                        <Select v-model="investorSelectValue">
                            <SelectTrigger class="clay-select w-full">
                                <SelectValue placeholder="-" />
                            </SelectTrigger>
                            <SelectContent
                                v-if="isClient"
                                class="w-(--reka-select-trigger-width)"
                            >
                                <div class="p-2">
                                    <Input
                                        v-model="investorQuery"
                                        class="clay-control h-9"
                                        placeholder="Search investor/host…"
                                    />
                                </div>
                                <SelectItem value="__none__">-</SelectItem>
                                <template v-if="filteredInvestors.length">
                                    <SelectItem
                                        v-for="u in filteredInvestors"
                                        :key="u.id"
                                        :value="String(u.id)"
                                    >
                                        {{ u.name }} ({{ u.email }}) — {{ u.role }}
                                    </SelectItem>
                                </template>
                                <div
                                    v-else
                                    class="px-3 py-2 text-sm text-muted-foreground"
                                >
                                    No results
                                </div>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.investor_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            v-model="form.status"
                            class="clay-select"
                        >
                            <option v-for="s in statuses" :key="s" :value="s">
                                {{ s }}
                            </option>
                        </select>
                        <InputError :message="form.errors.status" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <div v-if="isClient">
                            <QuillEditor
                                v-model:content="form.description"
                                content-type="html"
                                theme="snow"
                                class="clay-wysiwyg"
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
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="amenities">Fasilitas</Label>
                        <textarea
                            id="amenities"
                            v-model="form.amenities"
                            class="clay-textarea"
                            rows="6"
                            placeholder="+ Wifi&#10;+ AC&#10;+ Kolam renang"
                        />
                        <div class="text-xs text-[color:var(--clay-muted)]">
                            Tulis per baris dengan awalan tanda +.
                        </div>
                        <InputError :message="form.errors.amenities" />
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <Label>Gallery</Label>
                        </div>

                        <div
                            class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-soft)] px-4 py-8 text-center"
                            :class="[
                                galleryDragOver
                                    ? 'border-[color:var(--clay-ink)]'
                                    : '',
                            ]"
                            @click="galleryInputRef?.click()"
                            @dragenter.prevent="galleryDragOver = true"
                            @dragover.prevent="galleryDragOver = true"
                            @dragleave.prevent="galleryDragOver = false"
                            @drop="onGalleryDrop"
                        >
                            <div class="text-sm font-medium text-[color:var(--clay-ink)]">
                                Choose files or drag & drop here
                            </div>
                            <div class="text-xs text-[color:var(--clay-muted)]">
                                PNG/JPG up to 50MB each
                            </div>
                            <Button type="button" variant="outline">
                                Browse files
                            </Button>
                            <input
                                ref="galleryInputRef"
                                type="file"
                                accept="image/*"
                                multiple
                                class="hidden"
                                @change="onGalleryFilesSelected"
                            />
                        </div>

                        <div v-if="galleryPreviews.length" class="grid grid-cols-2 gap-3 md:grid-cols-3">
                            <div
                                v-for="(item, index) in galleryPreviews"
                                :key="`${item.file.name}-${index}`"
                                class="relative overflow-hidden rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-canvas)]"
                            >
                                <img
                                    :src="item.url"
                                    alt=""
                                    class="h-28 w-full object-cover"
                                />
                                <div class="flex items-center justify-between gap-2 px-3 py-2">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-[color:var(--clay-ink)]">
                                            {{ item.file.name }}
                                        </div>
                                        <div class="text-xs text-[color:var(--clay-muted)]">
                                            {{ Math.round(item.file.size / 1024) }} KB
                                        </div>
                                    </div>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="absolute top-2 right-2 bg-[color:var(--clay-canvas)]"
                                    @click="removeGalleryFile(index)"
                                >
                                    Remove
                                </Button>
                            </div>
                        </div>
                        <InputError :message="form.errors.gallery_files" />
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <Button :disabled="form.processing">Create</Button>
                        <Button variant="outline" as-child>
                            <Link href="/admin/properties">Cancel</Link>
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
