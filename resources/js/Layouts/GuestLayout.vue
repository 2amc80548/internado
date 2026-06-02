<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const dynamicStyle = computed(() => {
    const color = page.props.configuracion?.color_hexadecimal || '#0d9488';
    return `
        :root {
            --theme-primary: ${color};
            --theme-primary-hover: ${color}dd;
            --theme-primary-light: ${color}22;
        }
        .theme-primary-bg {
            background-color: var(--theme-primary) !important;
        }
        .theme-primary-text {
            color: var(--theme-primary) !important;
        }
        .theme-primary-border {
            border-color: var(--theme-primary) !important;
        }
    `;
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">
        <component :is="'style'">{{ dynamicStyle }}</component>

        <!-- Lado Izquierdo: Branding/Imagen -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-teal-900 justify-center items-center">
            <!-- Gradiente de fondo animado/elegante -->
            <div class="absolute inset-0 bg-gradient-to-br from-teal-800 via-teal-900 to-gray-900"></div>
            
            <!-- Patrón de puntos o decoración -->
            <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 32px 32px;"></div>
            
            <div class="relative z-10 p-12 text-center max-w-lg">
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-white/10 backdrop-blur-md border border-white/20 mb-8 shadow-2xl overflow-hidden p-2">
                    <img v-if="$page.props.configuracion?.ruta_logo_login" :src="`/storage/${$page.props.configuracion.ruta_logo_login}`" alt="Logo" class="max-h-20 max-w-20 object-contain rounded">
                    <svg v-else class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
                </div>
                <h1 class="text-4xl font-black text-white tracking-tight mb-4 leading-tight">Sistema de Gestión <br><span class="theme-primary-text uppercase block mt-2 text-2xl font-extrabold tracking-widest">{{ $page.props.configuracion?.nombre_sistema || 'Internado' }}</span></h1>
                <p class="text-gray-200 text-lg leading-relaxed mt-4">Administra pagos, asistencias, expedientes y toda la información estudiantil desde una plataforma moderna y centralizada.</p>
            </div>
        </div>

        <!-- Lado Derecho: Contenido/Formulario -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 sm:p-12 lg:p-24 relative overflow-y-auto max-h-screen">
            <!-- Bloques de decoración flotantes -->
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 rounded-full bg-teal-50 blur-3xl opacity-50 pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-blue-50 blur-3xl opacity-50 pointer-events-none"></div>

            <div class="w-full max-w-md relative z-10">
                <div class="lg:hidden mb-10 text-center flex flex-col items-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white p-2 mb-4 shadow-lg overflow-hidden border border-gray-100">
                        <img v-if="$page.props.configuracion?.ruta_logo_login" :src="`/storage/${$page.props.configuracion.ruta_logo_login}`" alt="Logo" class="max-h-12 max-w-12 object-contain rounded">
                        <svg v-else class="w-8 h-8 theme-primary-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path></svg>
                    </div>
                    <h2 class="text-2xl font-black text-gray-900 uppercase tracking-widest">{{ $page.props.configuracion?.nombre_sistema || 'Internado' }}</h2>
                </div>
                
                <slot />
            </div>
        </div>
    </div>
</template>
