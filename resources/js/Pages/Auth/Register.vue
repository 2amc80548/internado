<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    ci: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Solicitar Cuenta" />

        <div class="mb-8">
            <h2 class="text-3xl font-black text-gray-900 mb-2">Solicitar Cuenta</h2>
            <p class="text-gray-500 text-sm">Ingresa tu número de carnet para solicitar acceso al portal del estudiante. El correo es opcional.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            
            <!-- Carnet de Identidad -->
            <div>
                <InputLabel for="ci" value="Carnet de Identidad (CI)" />
                <TextInput id="ci" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" v-model="form.ci" required autofocus placeholder="Ingresa tu carnet registrado" />
                <InputError class="mt-2" :message="form.errors.ci" />
            </div>

            <!-- Correo Electrónico -->
            <div>
                <InputLabel for="email" value="Correo Electrónico (Opcional)" />
                <TextInput id="email" type="email" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" v-model="form.email" placeholder="correo@ejemplo.com (opcional)" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Contraseña -->
            <div>
                <InputLabel for="password" value="Nueva Contraseña" />
                <TextInput id="password" type="password" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" v-model="form.password" required />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <!-- Confirmar Contraseña -->
            <div>
                <InputLabel for="password_confirmation" value="Confirmar Contraseña" />
                <TextInput id="password_confirmation" type="password" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" v-model="form.password_confirmation" required />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-8 flex flex-col gap-4">
                <PrimaryButton class="w-full justify-center py-3.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-sm font-bold shadow-lg shadow-teal-500/30 transition-all" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Solicitar Cuenta de Estudiante
                </PrimaryButton>

                <p class="text-center text-sm text-gray-600">
                    ¿Ya tienes una cuenta?
                    <Link :href="route('login')" class="font-bold text-teal-600 hover:text-teal-800 transition">
                        Inicia sesión aquí
                    </Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>
