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

const hasPermission = (route) => {
    const role = getRoleName();
    if (role === 'Superadmin') return true;
    if (role === 'Encargada') {
        const permisos = page.props.configuracion?.permisos_encargada || [];
        return permisos.includes(route);
    }
    return false;
};

const menuItems = computed(() => [
    {
        name: 'Dashboard',
        route: 'dashboard',
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        show: hasPermission('dashboard')
    },
    {
        name: 'Estudiantes',
        route: 'estudiantes.index',
        icon: 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z',
        show: hasPermission('estudiantes.index')
    },
    {
        name: 'Usuarios y Roles',
        route: 'users.index',
        icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        show: hasPermission('users.index')
    },
    {
        name: 'Comunidades',
        route: 'comunidades.index',
        icon: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        show: hasPermission('comunidades.index')
    },
    {
        name: 'Grados Regulares',
        route: 'cursos.index',
        icon: 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z',
        show: hasPermission('cursos.index')
    },
    {
        name: 'Cursos BTH',
        route: 'cursos-bth.index',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        show: hasPermission('cursos-bth.index')
    },
    {
        name: 'Mensualidades (Pagos)',
        route: 'mensualidades.index',
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        show: hasPermission('mensualidades.index')
    },
    {
        name: 'Incidencias',
        route: 'incidencias.index',
        icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        show: hasPermission('incidencias.index')
    },
    {
        name: 'Configuración Sistema',
        route: 'configuracion.index',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        show: getRoleName() === 'Superadmin'
    }
]);

const visibleMenuItems = computed(() => menuItems.value.filter(item => item.show));

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
        .theme-primary-hover-bg:hover {
            background-color: var(--theme-primary-hover) !important;
        }
        .theme-primary-hover-text:hover {
            color: var(--theme-primary) !important;
        }
        .theme-primary-active-bg {
            background-color: var(--theme-primary-light) !important;
        }
        .focus-ring-primary:focus {
            --tw-ring-color: var(--theme-primary) !important;
        }
    `;
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">
        <component :is="'style'">{{ dynamicStyle }}</component>
        
        <!-- Sidebar Navigation -->
        <aside class="fixed inset-y-0 left-0 bg-gray-900 w-64 shadow-xl z-50 transform transition-transform duration-300 flex flex-col justify-between"
            :class="{ '-translate-x-full': !showingNavigationSidebar, 'translate-x-0': showingNavigationSidebar }">
            
            <div class="flex flex-col flex-1">
                <div class="flex items-center justify-between h-20 border-b border-gray-800 px-6">
                    <Link :href="route('dashboard')" class="flex items-center gap-3">
                        <div v-if="$page.props.configuracion?.ruta_logo" class="h-10 w-10 shrink-0 flex items-center justify-center bg-white/5 p-1 rounded-lg">
                            <img :src="`/storage/${$page.props.configuracion.ruta_logo}`" alt="Logo" class="max-h-8 max-w-8 object-contain rounded">
                        </div>
                        <div v-else class="text-white p-2 rounded-lg theme-primary-bg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <span class="text-white font-bold text-lg tracking-wider uppercase truncate max-w-[130px]">{{ $page.props.configuracion?.nombre_sistema || 'INTERNADO' }}</span>
                    </Link>
                    <button @click="showingNavigationSidebar = false" class="md:hidden text-gray-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <nav class="mt-6 px-4 space-y-2 overflow-y-auto flex-1">
                    <template v-for="item in visibleMenuItems" :key="item.name">
                        <Link :href="item.route && route().has(item.route) ? route(item.route) : '#'"
                            :class="[route().current(item.route) ? 'bg-gray-800 theme-primary-text font-extrabold shadow-sm' : 'text-gray-300 hover:bg-gray-800 hover:text-white font-medium', 'group flex items-center px-4 py-3 text-sm rounded-xl transition-all duration-200']">
                            <svg class="mr-3 flex-shrink-0 h-5 w-5 animate-pulse-slow" :class="[route().current(item.route) ? 'theme-primary-text' : 'text-gray-400 group-hover:text-white']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            {{ item.name }}
                        </Link>
                        
                        <!-- If this item is Dashboard and the user is a Student, render the active student tabs underneath -->
                        <div v-if="item.name === 'Dashboard' && getRoleName() === 'Estudiante' && $page.props.auth.estudiante?.estado_global !== 'Bachiller'" class="pl-4 space-y-1.5 border-l border-gray-800 ml-6 mt-2 mb-3">
                            <button @click="selectTab('resumen')" 
                                :class="[activeTab === 'resumen' ? 'bg-gray-800 theme-primary-text font-extrabold' : 'text-gray-300 hover:bg-gray-800 hover:text-white font-medium', 'w-full flex items-center px-4 py-3 text-sm rounded-xl transition-all duration-200 text-left']">
                                <svg class="mr-3 flex-shrink-0 h-5 w-5" :class="[activeTab === 'resumen' ? 'theme-primary-text' : 'text-gray-400 group-hover:text-white']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Mi Resumen
                            </button>
                            <button @click="selectTab('finanzas')" 
                                :class="[activeTab === 'finanzas' ? 'bg-gray-800 theme-primary-text font-extrabold' : 'text-gray-300 hover:bg-gray-800 hover:text-white font-medium', 'w-full flex items-center px-4 py-3 text-sm rounded-xl transition-all duration-200 text-left']">
                                <svg class="mr-3 flex-shrink-0 h-5 w-5" :class="[activeTab === 'finanzas' ? 'theme-primary-text' : 'text-gray-400 group-hover:text-white']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Mensualidad
                            </button>
                            <button @click="selectTab('academico')" 
                                :class="[activeTab === 'academico' ? 'bg-gray-800 theme-primary-text font-extrabold' : 'text-gray-300 hover:bg-gray-800 hover:text-white font-medium', 'w-full flex items-center px-4 py-3 text-sm rounded-xl transition-all duration-200 text-left']">
                                <svg class="mr-3 flex-shrink-0 h-5 w-5" :class="[activeTab === 'academico' ? 'theme-primary-text' : 'text-gray-400 group-hover:text-white']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                Boletines
                            </button>
                            <button @click="selectTab('trayectoria')" 
                                :class="[activeTab === 'trayectoria' ? 'bg-gray-800 theme-primary-text font-extrabold' : 'text-gray-300 hover:bg-gray-800 hover:text-white font-medium', 'w-full flex items-center px-4 py-3 text-sm rounded-xl transition-all duration-200 text-left']">
                                <svg class="mr-3 flex-shrink-0 h-5 w-5" :class="[activeTab === 'trayectoria' ? 'theme-primary-text' : 'text-gray-400 group-hover:text-white']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                </svg>
                                Trayectoria
                            </button>
                        </div>
                    </template>
                </nav>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col transition-all duration-300"
            :class="{ 'md:ml-64': showingNavigationSidebar, 'md:ml-0': !showingNavigationSidebar }">
            <!-- Topbar -->
            <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-40 sticky top-0">
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center">
                        <button @click="showingNavigationSidebar = !showingNavigationSidebar" class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none transition-colors mr-2">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                        <!-- Slot for dynamic header titles -->
                        <div class="hidden md:block ml-2">
                            <slot name="header" />
                        </div>
                    </div>
                    
                    <!-- Unified topbar user actions -->
                    <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                        <div class="flex flex-col text-right hidden sm:block">
                            <span class="text-sm font-bold text-gray-800 leading-tight block">
                                {{ $page.props.auth.user.name }}
                            </span>
                            <span class="text-xs font-semibold theme-primary-text tracking-wider uppercase block mt-0.5">
                                {{ getRoleName() }}
                            </span>
                        </div>
                        <div class="h-9 w-9 rounded-full bg-gradient-to-tr from-gray-800 to-gray-700 theme-primary-bg flex items-center justify-center text-white font-extrabold shadow-sm text-sm shrink-0 border border-white/20 uppercase">
                            {{ $page.props.auth.user.name.charAt(0) }}
                        </div>
                        <div class="flex items-center gap-1.5 sm:gap-2 border-l border-gray-200 pl-3">
                            <Link :href="route('profile.edit')" class="text-xs font-bold text-gray-600 hover:text-white hover:theme-primary-bg bg-gray-50 hover:shadow-sm px-2.5 sm:px-3 py-1.5 rounded-lg border border-gray-200 transition-all duration-250 flex items-center gap-1">
                                <span>👤</span> <span class="hidden md:inline">Mi Perfil</span>
                            </Link>
                            <Link :href="route('logout')" method="post" as="button" class="text-xs font-bold text-red-600 hover:text-white hover:bg-red-500 bg-red-50 hover:shadow-sm px-2.5 sm:px-3 py-1.5 rounded-lg border border-red-200 hover:border-red-500 transition-all duration-200 flex items-center gap-1">
                                <span>🚪</span> <span class="hidden md:inline">Salir</span>
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
