<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    ci: '',
    nombre: '',
    ap_paterno: '',
    ap_materno: '',
    celular: '',
    email: '',
    password: '',
    password_confirmation: '',
    foto: null,
});

const fotoPreview = ref(null);

const handleFotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.foto = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            fotoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

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
            <h2 class="text-3xl font-black text-gray-900 mb-2">Crear Cuenta</h2>
            <p class="text-gray-500 text-sm">Completa tus datos personales para solicitar acceso al internado como estudiante.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            
            <!-- Foto de Perfil -->
            <div class="flex justify-center mb-6">
                <div class="relative group cursor-pointer">
                    <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-white shadow-lg bg-gray-100 flex items-center justify-center">
                        <img v-if="fotoPreview" :src="fotoPreview" class="w-full h-full object-cover">
                        <svg v-else class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </div>
                    <label class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition cursor-pointer">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <input type="file" class="hidden" accept="image/*" @change="handleFotoChange">
                    </label>
                </div>
            </div>
            <InputError class="text-center" :message="form.errors.foto" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Carnet de Identidad -->
                <div>
                    <InputLabel for="ci" value="Carnet de Identidad (CI)" />
                    <TextInput id="ci" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" v-model="form.ci" required autofocus />
                    <InputError class="mt-2" :message="form.errors.ci" />
                </div>

                <!-- Nombre -->
                <div>
                    <InputLabel for="nombre" value="Nombres" />
                    <TextInput id="nombre" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" v-model="form.nombre" required />
                    <InputError class="mt-2" :message="form.errors.nombre" />
                </div>

                <!-- Apellido Paterno -->
                <div>
                    <InputLabel for="ap_paterno" value="Apellido Paterno" />
                    <TextInput id="ap_paterno" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" v-model="form.ap_paterno" required />
                    <InputError class="mt-2" :message="form.errors.ap_paterno" />
                </div>

                <!-- Apellido Materno -->
                <div>
                    <InputLabel for="ap_materno" value="Apellido Materno" />
                    <TextInput id="ap_materno" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" v-model="form.ap_materno" />
                    <InputError class="mt-2" :message="form.errors.ap_materno" />
                </div>

                <!-- Celular -->
                <div>
                    <InputLabel for="celular" value="Número de Celular" />
                    <TextInput id="celular" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" v-model="form.celular" />
                    <InputError class="mt-2" :message="form.errors.celular" />
                </div>

                <!-- Correo Electrónico -->
                <div>
                    <InputLabel for="email" value="Correo Electrónico" />
                    <TextInput id="email" type="email" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" v-model="form.email" required />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <!-- Contraseña -->
                <div>
                    <InputLabel for="password" value="Contraseña" />
                    <TextInput id="password" type="password" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" v-model="form.password" required />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <!-- Confirmar Contraseña -->
                <div>
                    <InputLabel for="password_confirmation" value="Confirmar Contraseña" />
                    <TextInput id="password_confirmation" type="password" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" v-model="form.password_confirmation" required />
                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>
            </div>

            <div class="mt-8 flex flex-col gap-4">
                <PrimaryButton class="w-full justify-center py-3.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-sm font-bold shadow-lg shadow-teal-500/30 transition-all" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Solicitar Cuenta
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
