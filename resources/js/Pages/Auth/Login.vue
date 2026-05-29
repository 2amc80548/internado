<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar Sesión" />

        <div v-if="status" class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-sm font-medium text-green-700 flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ status }}
        </div>

        <div class="mb-8">
            <h2 class="text-3xl font-black text-gray-900 mb-2">Bienvenido de nuevo</h2>
            <p class="text-gray-500 text-sm">Ingresa tus credenciales para acceder a tu cuenta.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="login" value="Carnet de Identidad (CI) o Correo" />
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <TextInput id="login" type="text" class="pl-10 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" v-model="form.login" required autofocus autocomplete="username" placeholder="Ej. 1234567" />
                </div>
                <InputError class="mt-2" :message="form.errors.login" />
            </div>

            <div>
                <InputLabel for="password" value="Contraseña" />
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <TextInput id="password" type="password" class="pl-10 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" v-model="form.password" required autocomplete="current-password" placeholder="••••••••" />
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between mt-6">
                <label class="flex items-center group cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" class="text-teal-600 focus:ring-teal-500 rounded border-gray-300" />
                    <span class="ml-2 text-sm text-gray-600 group-hover:text-gray-900 transition">Recordarme</span>
                </label>

                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm font-bold text-teal-600 hover:text-teal-800 transition">
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <div class="mt-8 flex flex-col gap-4">
                <PrimaryButton class="w-full justify-center py-3.5 rounded-xl bg-gray-900 hover:bg-black text-sm font-bold shadow-lg transition-all" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Iniciar Sesión
                </PrimaryButton>

                <div class="relative flex items-center py-2">
                    <div class="flex-grow border-t border-gray-200"></div>
                    <span class="flex-shrink-0 mx-4 text-gray-400 text-sm">O</span>
                    <div class="flex-grow border-t border-gray-200"></div>
                </div>

                <Link :href="route('register')" class="w-full justify-center text-center py-3.5 rounded-xl bg-white border-2 border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300 text-sm font-bold shadow-sm transition-all">
                    Solicitar Cuenta (Estudiantes)
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
