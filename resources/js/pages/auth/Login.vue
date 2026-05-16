<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <Head title="Log in" />

    <div class="px-4 py-6">
        <div v-if="status" class="mb-3 text-sm font-medium text-green-700">
            {{ status }}
        </div>

        <div class="mb-6">
            <div class="text-xl font-semibold tracking-tight text-[color:var(--clay-ink)]">
                Masuk
            </div>
            <div class="mt-1 text-sm text-[color:var(--clay-muted)]">
                Gunakan email dan password untuk melanjutkan
            </div>
        </div>

        <div class="rounded-lg border border-[color:var(--clay-hairline)] bg-[color:var(--clay-surface-card)] p-4">
            <Form
                v-bind="store.form()"
                :reset-on-success="['password']"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-5"
            >
                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        class="clay-control"
                        placeholder="email@contoh.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Password</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-sm"
                            :tabindex="5"
                        >
                            Lupa password?
                        </TextLink>
                    </div>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>Ingat saya</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="w-full"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    Masuk
                </Button>

                <div v-if="canRegister" class="text-center text-sm text-[color:var(--clay-muted)]">
                    Belum punya akun?
                    <TextLink :href="register()" :tabindex="5">Daftar</TextLink>
                </div>
            </Form>
        </div>
    </div>
</template>
