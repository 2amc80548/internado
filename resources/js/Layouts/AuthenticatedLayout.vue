<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationSidebar = ref(false);
const page = usePage();

const getRoleName = () => {
    return page.props.auth.user?.rol?.nombre || 'Usuario';
};

const menuItems = [
    {
        name: 'Dashboard',
        route: 'dashboard',
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        show: true
    },
    {
        name: 'Gestión de Usuarios',
        route: 'users.index',
        icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        show: getRoleName() === 'Superadmin'
    },
    {
        name: 'Comunidades',
        route: 'comunidades.index',
        icon: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        show: getRoleName() === 'Superadmin'
    },
    {
        name: 'Grados Regulares',
        route: 'cursos.index',
        icon: 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z',
        show: getRoleName() === 'Superadmin' || getRoleName() === 'Encargada'
    },
    {
        name: 'Cursos BTH',
        route: 'cursos-bth.index',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        show: getRoleName() === 'Superadmin' || getRoleName() === 'Encargada'
    },
    {
        name: 'Mensualidades (Pagos)',
        route: 'mensualidades.index',
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        show: getRoleName() === 'Superadmin' || getRoleName() === 'Encargada'
    },
    {
        name: 'Configuración Sistema',
        route: 'configuracion.index',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        show: getRoleName() === 'Superadmin'
    }
];

const visibleMenuItems = menuItems.filter(item => item.show);
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">
        
        <!-- Sidebar Navigation -->
        <aside class="fixed inset-y-0 left-0 bg-gray-900 w-64 shadow-xl z-50 transform transition-transform duration-300 md:translate-x-0"
            :class="{ '-translate-x-full': !showingNavigationSidebar, 'translate-x-0': showingNavigationSidebar }">
            
            <div class="flex items-center justify-between h-20 border-b border-gray-800 px-6">
                <Link :href="route('dashboard')" class="flex items-center gap-3">
                    <div class="bg-teal-500 text-white p-2 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <span class="text-white font-bold text-xl tracking-wider">INTERNADO</span>
                </Link>
                <button @click="showingNavigationSidebar = false" class="md:hidden text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- User Profile Widget (Movido a la barra superior) -->

            <nav class="mt-6 px-4 space-y-2">
                <Link v-for="item in visibleMenuItems" :key="item.name" :href="item.route && route().has(item.route) ? route(item.route) : '#'"
                    :class="[route().current(item.route) ? 'bg-gray-800 text-teal-400' : 'text-gray-300 hover:bg-gray-800 hover:text-white', 'group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200']">
                    <svg class="mr-3 flex-shrink-0 h-5 w-5" :class="[route().current(item.route) ? 'text-teal-400' : 'text-gray-400 group-hover:text-white']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    {{ item.name }}
                </Link>
            </nav>

            <!-- Logout movido a topbar -->
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
            <!-- Topbar (Mobile Hamburger & Quick Actions) -->
            <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-40 sticky top-0">
                <div class="flex items-center">
                    <button @click="showingNavigationSidebar = true" class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <!-- Slot for dynamic header titles -->
                    <div class="hidden md:block ml-4">
                        <slot name="header" />
                    </div>
                </div>
                
                <div class="flex items-center gap-6">
                    <div class="hidden sm:flex items-center gap-3 border-r pr-6">
                        <div class="text-right">
                            <p class="text-sm font-bold text-gray-800 leading-tight">{{ $page.props.auth.user.name }}</p>
                            <p class="text-xs text-teal-600 font-medium">{{ getRoleName() }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-teal-500 to-blue-600 flex items-center justify-center text-white font-bold shadow-md">
                            {{ $page.props.auth.user.name.charAt(0) }}
                        </div>
                    </div>
                    <Link :href="route('profile.edit')" class="text-sm font-medium text-gray-500 hover:text-teal-600 transition">
                        Mi Perfil
                    </Link>
                    <Link :href="route('logout')" method="post" as="button" class="text-sm font-medium text-red-500 hover:text-red-700 transition">
                        Salir
                    </Link>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 relative pb-12">
                <div class="md:hidden px-4 py-4 border-b bg-white shadow-sm" v-if="$slots.header">
                    <slot name="header" />
                </div>

                <!-- Flash Messages -->
                <div v-if="$page.props.flash?.success" class="m-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm flex items-center transition-all">
                    <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="font-medium">{{ $page.props.flash.success }}</span>
                </div>
                <div v-if="$page.props.flash?.error" class="m-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded shadow-sm flex items-center transition-all">
                    <svg class="w-6 h-6 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="font-medium">{{ $page.props.flash.error }}</span>
                </div>

                <slot />
            </main>
        </div>
        
        <!-- Mobile Overlay -->
        <div v-if="showingNavigationSidebar" @click="showingNavigationSidebar = false" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-40 md:hidden"></div>
    </div>
</template>
