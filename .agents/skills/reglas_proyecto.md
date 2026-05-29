# Reglas de Desarrollo: Sistema de Internado

## Stack Tecnológico
* Framework Backend: Laravel
* Framework Frontend: Vue 3
* Integración: Inertia.js
* Lenguaje Frontend: TypeScript
* Estilos: Tailwind CSS

## Reglas de Backend (Laravel)
* Aplica el patrón MVC estrictamente y mantén los controladores delgados.
* Devuelve todas las vistas y respuestas del frontend utilizando `Inertia::render()`.
* Respeta los nombres de las tablas, campos y relaciones exactamente como se definen en el "Prompt Maestro" de la base de datos proporcionado por el usuario.
* Implementa Form Requests para toda validación de datos entrantes antes de que toquen el controlador.
* Las migraciones deben crearse en el orden correcto para respetar las llaves foráneas (FK) sin generar errores en la base de datos.

## Reglas de Frontend (Vue 3 + TypeScript)
* Programa los componentes utilizando siempre la Composition API con `<script setup lang="ts">`.
* Define interfaces de TypeScript claras para todas las propiedades (props) y datos que lleguen desde el backend a través de Inertia.
* Prioriza el uso de Composables para extraer la lógica reutilizable de los componentes de Vue.
* Diseña componentes modulares para los formularios, botones y modales, facilitando su reutilización en el panel administrativo y el portal de estudiantes.

## Entorno Local y Pruebas
* Asegura que las rutas y configuraciones de Vite/Inertia estén optimizadas para compilar y probar la aplicación rápidamente en el entorno local (localhost).
* Al generar comandos de ejecución, asume el flujo de trabajo estándar de desarrollo local (`php artisan serve` y `npm run dev`).
