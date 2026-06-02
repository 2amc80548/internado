<script setup lang="ts">
// Definición de las propiedades del componente
defineProps<{
    filteredMensualidades: any[];
}>();

// Definición de los eventos emitidos por el componente
const emit = defineEmits<{
    (e: 'pay', payment: any): void;
    (e: 'delete', id: number): void;
    (e: 'anular', payment: any): void;
    (e: 'viewAnulado', payment: any): void;
}>();
</script>

<template>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estudiante</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Mes</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Monto</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <!-- Iteración sobre las mensualidades filtradas -->
            <tr v-for="m in filteredMensualidades" :key="m.id" class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 whitespace-nowrap">
                    <!-- Nombre y datos informativos del estudiante -->
                    <div class="text-sm font-bold text-gray-900">{{ m.registro_internado?.estudiante?.persona?.nombre }} {{ m.registro_internado?.estudiante?.persona?.ap_paterno }}</div>
                    <div class="flex flex-wrap gap-1.5 mt-1.5">
                        <span class="text-[10px] text-gray-500 font-bold bg-gray-100 px-2 py-0.5 rounded-md border border-gray-200/60">CI: {{ m.registro_internado?.estudiante?.persona?.ci }}</span>
                        <span class="text-[10px] text-teal-700 font-extrabold bg-teal-50 px-2 py-0.5 rounded-md border border-teal-100">📅 {{ m.registro_internado?.gestion?.nombre_gestion }}</span>
                        <span v-if="m.registro_internado?.curso" class="text-[10px] text-indigo-700 font-extrabold bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">🎓 {{ m.registro_internado?.curso?.nombre }}</span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ m.mes }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    Bs. {{ m.total }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <!-- Visualización del estado del pago -->
                    <span v-if="m.estado === 'Pagado'" class="inline-flex flex-col">
                        <span class="px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800 self-start">Pagado ({{ m.metodo_pago }})</span>
                        <span class="text-[10px] text-gray-500 font-bold mt-1">📅 {{ m.fecha_pago }}</span>
                    </span>
                    <span v-else-if="m.estado === 'Anulado'" class="inline-flex flex-col">
                        <span class="px-3 py-1 text-xs font-bold rounded-full bg-rose-100 text-rose-800 self-start">Anulado</span>
                        <span class="text-[10px] text-rose-500 font-bold mt-1">Ver motivo 👁️</span>
                    </span>
                    <span v-else class="px-3 py-1 text-xs font-bold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <!-- Acciones según el estado del pago -->
                    
                    <!-- Pago Pendiente -->
                    <template v-if="m.estado === 'Pendiente' || m.estado === 'Pendiente_Verificacion'">
                        <button @click="emit('pay', m)" class="inline-flex items-center gap-1 px-3 py-1.5 bg-teal-50 hover:bg-teal-100 text-teal-600 rounded-xl text-xs font-bold transition">
                            💵 Cobrar
                        </button>
                        <button @click="emit('delete', m.id)" class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-xs font-bold transition">
                            ❌ Eliminar
                        </button>
                    </template>
                    
                    <!-- Pago Realizado (Pagado) -->
                    <template v-else-if="m.estado === 'Pagado'">
                        <button @click="emit('anular', m)" class="inline-flex items-center gap-1 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-bold transition">
                            🚫 Anular
                        </button>
                    </template>
                    
                    <!-- Pago Anulado -->
                    <template v-else-if="m.estado === 'Anulado'">
                        <button @click="emit('viewAnulado', m)" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-xl text-xs font-bold transition">
                            👁️ Ver Motivo
                        </button>
                    </template>
                </td>
            </tr>
            <!-- Mostrar mensaje si no hay resultados -->
            <tr v-if="filteredMensualidades.length === 0">
                <td colspan="5" class="px-6 py-8 text-center text-gray-500 font-semibold text-sm">No se encontraron registros de mensualidades.</td>
            </tr>
        </tbody>
    </table>
</template>
