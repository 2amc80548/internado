<script setup>
import { ref, provide, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const showingNavigationSidebar = ref(typeof window !== 'undefined' ? window.innerWidth >= 768 : true);

const activeTab = computed(() => {
    const url = new URL(page.url, window.location.origin);
    const tabParam = url.searchParams.get('tab');
    return tabParam && ['resumen', 'finanzas', 'academico', 'trayectoria'].includes(tabParam) 
        ? tabParam 
        : 'resumen';
});

const selectTab = (tab) => {
    router.visit(route('estudiante.portal', { tab }), {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

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
        <aside class="fixed inset-y-0 left-0 bg-gray-900 w-64 shadow-xl z-50 transform transition-transform duration-300 flex flex-col justify-between"
            :class="{ '-translate-x-full': !showingNavigationSidebar, 'translate-x-0': showingNavigationSidebar }">
            
            <div class="flex flex-col flex-1">
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

                <nav class="mt-6 px-4 space-y-2 overflow-y-auto flex-1">
                    <template v-for="item in visibleMenuItems" :key="item.name">
                        <Link :href="item.route && route().has(item.route) ? route(item.route) : '#'"
                            :class="[route().current(item.route) ? 'bg-gray-800 text-teal-400 font-extrabold' : 'text-gray-300 hover:bg-gray-800 hover:text-white font-medium', 'group flex items-center px-4 py-3 text-sm rounded-xl transition-all duration-200']">
                            <svg class="mr-3 flex-shrink-0 h-5 w-5" :class="[route().current(item.route) ? 'text-teal-400' : 'text-gray-400 group-hover:text-white']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            {{ item.name }}
                        </Link>
                        
                        <!-- If this item is Dashboard and the user is a Student, render the active student tabs underneath -->
                        <div v-if="item.name === 'Dashboard' && getRoleName() === 'Estudiante' && $page.props.auth.estudiante?.estado_global !== 'Bachiller'" class="pl-4 space-y-0.5 border-l border-gray-800 ml-6 mt-1 mb-2">
                            <button @click="selectTab('resumen')" 
                                :class="activeTab === 'resumen' ? 'text-teal-400 font-black bg-gray-800/60 shadow-sm border-l-2 border-teal-500' : 'text-gray-400 hover:bg-gray-800/20 hover:text-white font-bold border-l-2 border-transparent'" 
                                class="w-full flex items-center px-3.5 py-2 text-xs rounded-r-lg transition-all duration-200 gap-2 text-left">
                                <span>📊</span> Mi Resumen
                            </button>
                            <button @click="selectTab('finanzas')" 
                                :class="activeTab === 'finanzas' ? 'text-teal-400 font-black bg-gray-800/60 shadow-sm border-l-2 border-teal-500' : 'text-gray-400 hover:bg-gray-800/20 hover:text-white font-bold border-l-2 border-transparent'" 
                                class="w-full flex items-center px-3.5 py-2 text-xs rounded-r-lg transition-all duration-200 gap-2 text-left">
                                <span>💰</span> Mensualidad
                            </button>
                            <button @click="selectTab('academico')" 
                                :class="activeTab === 'academico' ? 'text-teal-400 font-black bg-gray-800/60 shadow-sm border-l-2 border-teal-500' : 'text-gray-400 hover:bg-gray-800/20 hover:text-white font-bold border-l-2 border-transparent'" 
                                class="w-full flex items-center px-3.5 py-2 text-xs rounded-r-lg transition-all duration-200 gap-2 text-left">
                                <span>📚</span> Boletines
                            </button>
                            <button @click="selectTab('trayectoria')" 
                                :class="activeTab === 'trayectoria' ? 'text-teal-400 font-black bg-gray-800/60 shadow-sm border-l-2 border-teal-500' : 'text-gray-400 hover:bg-gray-800/20 hover:text-white font-bold border-l-2 border-transparent'" 
                                class="w-full flex items-center px-3.5 py-2 text-xs rounded-r-lg transition-all duration-200 gap-2 text-left">
                                <span>🎓</span> Trayectoria
                            </button>
                        </div>
                    </template>
                </nav>
            </div>

            <!-- Sidebar Footer: Profile and Logout (Only for non-students) -->
            <div v-if="getRoleName() !== 'Estudiante'" class="p-4 border-t border-gray-800 bg-gray-950/50">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-10 w-10 rounded-full bg-gradient-to-r from-teal-500 to-blue-600 flex items-center justify-center text-white font-bold shadow-md shrink-0">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                    <div class="text-left overflow-hidden">
                        <p class="text-sm font-bold text-gray-200 leading-tight truncate">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-teal-400 font-medium truncate">{{ getRoleName() }}</p>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <Link :href="route('profile.edit')" class="flex items-center justify-center gap-2 w-full py-2 px-4 rounded-xl text-sm font-semibold text-gray-300 bg-gray-800 hover:bg-gray-700 hover:text-white transition-all duration-200">
                        <span>👤</span> Mi Perfil
                    </Link>
                    <Link :href="route('logout')" method="post" as="button" class="flex items-center justify-center gap-2 w-full py-2 px-4 rounded-xl text-sm font-semibold text-red-400 bg-red-500/10 hover:bg-red-500 hover:text-white border border-red-500/20 transition-all duration-200">
                        <span>🚪</span> Salir
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col transition-all duration-300"
            :class="{ 'md:ml-64': showingNavigationSidebar, 'md:ml-0': !showingNavigationSidebar }">
            <!-- Topbar (Mobile Hamburger & Quick Actions) -->
            <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-40 sticky top-0">
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center">
                        <button @click="showingNavigationSidebar = !showingNavigationSidebar" class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                        <!-- Slot for dynamic header titles -->
                        <div class="hidden md:block ml-4">
                            <slot name="header" />
                        </div>
                    </div>
                    
                    <!-- Student specific topbar user actions -->
                    <div v-if="getRoleName() === 'Estudiante'" class="flex items-center gap-2 sm:gap-4 shrink-0">
                        <span class="hidden md:inline text-sm font-semibold text-gray-700">
                            {{ $page.props.auth.user.name }}
                        </span>
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <Link :href="route('profile.edit')" class="text-xs font-bold text-gray-600 hover:text-teal-600 bg-gray-100 hover:bg-teal-50 px-2 sm:px-3 py-1.5 rounded-lg transition-all duration-200">
                                👤 <span class="hidden sm:inline">Mi Perfil</span>
                            </Link>
                            <Link :href="route('logout')" method="post" as="button" class="text-xs font-bold text-red-600 hover:text-white bg-red-50 hover:bg-red-500 px-2 sm:px-3 py-1.5 rounded-lg border border-red-100 hover:border-red-500 transition-all duration-200">
                                🚪 <span class="hidden sm:inline">Salir</span>
                            </Link>
                        </div>
                    </div>
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
