<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Recuperar Contraseña" />

        <div class="mb-8">
            <h2 class="text-3xl font-black text-gray-900 mb-2">Recuperar Acceso</h2>
            <p class="text-gray-500 text-sm">¿Olvidaste tu contraseña? No hay problema. Ingresa tu correo electrónico y te enviaremos un enlace para restablecerla y elegir una nueva.</p>
        </div>

        <div v-if="status" class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-sm font-medium text-green-700 flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" value="Correo Electrónico" />
                <TextInput id="email" type="email" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" v-model="form.email" required autofocus autocomplete="username" placeholder="ejemplo@correo.com" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-8 flex flex-col gap-4">
                <PrimaryButton class="w-full justify-center py-3.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-sm font-bold shadow-lg shadow-teal-500/30 transition-all" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Enviar Enlace de Recuperación
                </PrimaryButton>

                <Link :href="route('login')" class="w-full justify-center text-center py-3.5 rounded-xl bg-white border-2 border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300 text-sm font-bold shadow-sm transition-all">
                    Volver al Inicio de Sesión
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
