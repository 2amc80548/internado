<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps<{
  show: boolean;
  title?: string;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'cropped', file: File): void;
}>();

// UI States: 'select' | 'camera' | 'crop'
const mode = ref<'select' | 'camera' | 'crop'>('select');

// Camera Stream
const videoElement = ref<HTMLVideoElement | null>(null);
let stream: MediaStream | null = null;
const cameraError = ref<string | null>(null);

// Crop Canvas
const workspaceCanvas = ref<HTMLCanvasElement | null>(null);
const originalImage = ref<HTMLImageElement | null>(null);

// Transform States
const zoom = ref(1); // 1 = original scale fit
const minZoom = ref(0.1);
const maxZoom = ref(5);
const offsetX = ref(0);
const offsetY = ref(0);

// Interaction States
const isDragging = ref(false);
const startDragX = ref(0);
const startDragY = ref(0);

// Crop settings
const CROP_SIZE = 400; // Output dimension (strictly square)

// Watch props.show to reset states or stop camera
watch(() => props.show, (newVal) => {
  if (!newVal) {
    stopCamera();
  } else {
    mode.value = 'select';
    originalImage.value = null;
    zoom.value = 1;
    offsetX.value = 0;
    offsetY.value = 0;
  }
});

const startCamera = async () => {
  cameraError.value = null;
  mode.value = 'camera';
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { width: 640, height: 640, facingMode: 'user' },
      audio: false
    });
    if (videoElement.value) {
      videoElement.value.srcObject = stream;
    }
  } catch (err: any) {
    console.error('Error al acceder a la cámara:', err);
    cameraError.value = 'No se pudo acceder a la cámara. Asegúrate de otorgar los permisos necesarios.';
  }
};

const stopCamera = () => {
  if (stream) {
    stream.getTracks().forEach(track => track.stop());
    stream = null;
  }
  if (videoElement.value) {
    videoElement.value.srcObject = null;
  }
};

const captureFromCamera = () => {
  if (!videoElement.value) return;
  
  const video = videoElement.value;
  // Create an offscreen canvas to capture the raw video frame
  const captureCanvas = document.createElement('canvas');
  const size = Math.min(video.videoWidth, video.videoHeight);
  captureCanvas.width = size;
  captureCanvas.height = size;
  
  const ctx = captureCanvas.getContext('2d');
  if (ctx) {
    // Draw centered square from video
    const startX = (video.videoWidth - size) / 2;
    const startY = (video.videoHeight - size) / 2;
    ctx.drawImage(video, startX, startY, size, size, 0, 0, size, size);
  }
  
  const img = new Image();
  img.onload = () => {
    originalImage.value = img;
    stopCamera();
    mode.value = 'crop';
    initCropTransforms();
  };
  img.src = captureCanvas.toDataURL('image/jpeg');
};

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = new Image();
      img.onload = () => {
        originalImage.value = img;
        mode.value = 'crop';
        initCropTransforms();
      };
      img.src = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
};

const initCropTransforms = () => {
  if (!originalImage.value) return;
  const img = originalImage.value;
  // Fit image inside crop viewport by default
  const scale = Math.max(CROP_SIZE / img.width, CROP_SIZE / img.height);
  zoom.value = scale;
  minZoom.value = scale * 0.5;
  maxZoom.value = scale * 4;
  offsetX.value = 0;
  offsetY.value = 0;
  
  // Render initially
  setTimeout(renderCropCanvas, 50);
};

const renderCropCanvas = () => {
  if (!workspaceCanvas.value || !originalImage.value) return;
  
  const canvas = workspaceCanvas.value;
  const ctx = canvas.getContext('2d');
  if (!ctx) return;
  
  const img = originalImage.value;
  
  // Clear canvas
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  
  // Center coordinates of the canvas
  const centerX = canvas.width / 2;
  const centerY = canvas.height / 2;
  
  ctx.save();
  // Translate to center, apply transforms, then draw image relative to it
  ctx.translate(centerX + offsetX.value, centerY + offsetY.value);
  ctx.scale(zoom.value, zoom.value);
  ctx.drawImage(img, -img.width / 2, -img.height / 2);
  ctx.restore();
  
  // Draw darken overlays outside the square crop viewport
  const cropLeft = (canvas.width - CROP_SIZE) / 2;
  const cropTop = (canvas.height - CROP_SIZE) / 2;
  
  ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
  
  // Top overlay
  ctx.fillRect(0, 0, canvas.width, cropTop);
  // Bottom overlay
  ctx.fillRect(0, cropTop + CROP_SIZE, canvas.width, canvas.height - (cropTop + CROP_SIZE));
  // Left overlay
  ctx.fillRect(0, cropTop, cropLeft, CROP_SIZE);
  // Right overlay
  ctx.fillRect(cropLeft + CROP_SIZE, cropTop, canvas.width - (cropLeft + CROP_SIZE), CROP_SIZE);
  
  // Draw square viewport border
  ctx.strokeStyle = '#14b8a6'; // Teal border
  ctx.lineWidth = 3;
  ctx.strokeRect(cropLeft, cropTop, CROP_SIZE, CROP_SIZE);
};

// Canvas dragging/interaction
const onMouseDown = (e: MouseEvent) => {
  if (mode.value !== 'crop') return;
  isDragging.value = true;
  startDragX.value = e.clientX - offsetX.value;
  startDragY.value = e.clientY - offsetY.value;
};

const onMouseMove = (e: MouseEvent) => {
  if (!isDragging.value) return;
  offsetX.value = e.clientX - startDragX.value;
  offsetY.value = e.clientY - startDragY.value;
  renderCropCanvas();
};

const onMouseUp = () => {
  isDragging.value = false;
};

// Touch support for mobile
const onTouchStart = (e: TouchEvent) => {
  if (mode.value !== 'crop' || e.touches.length === 0) return;
  isDragging.value = true;
  startDragX.value = e.touches[0].clientX - offsetX.value;
  startDragY.value = e.touches[0].clientY - offsetY.value;
};

const onTouchMove = (e: TouchEvent) => {
  if (!isDragging.value || e.touches.length === 0) return;
  offsetX.value = e.touches[0].clientX - startDragX.value;
  offsetY.value = e.touches[0].clientY - startDragY.value;
  renderCropCanvas();
};

// Apply zoom changes
const onZoomChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  zoom.value = parseFloat(target.value);
  renderCropCanvas();
};

const adjustZoom = (amount: number) => {
  zoom.value = Math.max(minZoom.value, Math.min(maxZoom.value, zoom.value + amount));
  renderCropCanvas();
};

// Crop execution
const finishCrop = () => {
  if (!originalImage.value || !workspaceCanvas.value) return;
  
  const img = originalImage.value;
  const canvas = workspaceCanvas.value;
  
  // Create offscreen canvas for exact output size
  const outputCanvas = document.createElement('canvas');
  outputCanvas.width = CROP_SIZE;
  outputCanvas.height = CROP_SIZE;
  
  const ctx = outputCanvas.getContext('2d');
  if (!ctx) return;
  
  // We need to calculate what part of the original image falls inside the crop viewport
  // Viewport center on workspace is canvas.width / 2, canvas.height / 2
  // Offsets are applied to center.
  // Transform back to original image space
  ctx.save();
  // Translate to center of output square
  ctx.translate(CROP_SIZE / 2, CROP_SIZE / 2);
  // Apply the same scale
  ctx.scale(zoom.value, zoom.value);
  // Translate by the offset
  ctx.translate(offsetX.value / zoom.value, offsetY.value / zoom.value);
  // Draw original image relative to its center
  ctx.drawImage(img, -img.width / 2, -img.height / 2);
  ctx.restore();
  
  outputCanvas.toBlob((blob) => {
    if (blob) {
      const croppedFile = new File([blob], 'avatar_crop.jpg', { type: 'image/jpeg', lastModified: Date.now() });
      emit('cropped', croppedFile);
      emit('close');
    }
  }, 'image/jpeg', 0.9);
};

onMounted(() => {
  if (props.show) {
    mode.value = 'select';
  }
});

onUnmounted(() => {
  stopCamera();
});
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
    <!-- Backdrop overlay -->
    <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity" @click="emit('close')"></div>
    
    <!-- Modal card -->
    <div class="relative bg-slate-900 border border-slate-800 text-slate-100 rounded-3xl max-w-xl w-full overflow-hidden shadow-2xl transition-all flex flex-col z-10 animate-fade-in">
      
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
        <h3 class="text-lg font-black text-teal-400 uppercase tracking-wider">
          {{ title || 'Fotografía de Perfil' }}
        </h3>
        <button @click="emit('close')" class="text-slate-400 hover:text-white rounded-lg p-1 bg-slate-800 hover:bg-slate-700 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>
      
      <!-- Modal Content Body -->
      <div class="p-6 flex flex-col items-center justify-center min-h-[380px] bg-slate-950/30">
        
        <!-- STEP 1: Select Option -->
        <div v-if="mode === 'select'" class="space-y-6 w-full max-w-sm text-center">
          <p class="text-slate-400 text-sm">Elige una opción para actualizar la fotografía de perfil. La imagen será estrictamente encuadrada de forma cuadrada.</p>
          
          <div class="grid grid-cols-1 gap-4">
            <button @click="startCamera" class="flex items-center justify-center gap-3 w-full py-4 px-6 rounded-2xl bg-teal-600 hover:bg-teal-500 text-white font-extrabold shadow-lg shadow-teal-900/30 transition duration-300">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
              Tomar Foto con Cámara
            </button>
            
            <label class="flex items-center justify-center gap-3 w-full py-4 px-6 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-100 border border-slate-700 font-extrabold cursor-pointer transition duration-300">
              <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
              Subir Archivo de Imagen
              <input type="file" class="hidden" accept="image/*" @change="handleFileUpload">
            </label>
          </div>
        </div>
        
        <!-- STEP 2: Webcam Stream -->
        <div v-if="mode === 'camera'" class="relative w-full max-w-sm flex flex-col items-center gap-4">
          <div v-if="cameraError" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm text-center">
            {{ cameraError }}
            <button @click="mode = 'select'" class="mt-3 block mx-auto py-1.5 px-4 bg-slate-800 text-white rounded-lg text-xs font-bold">Volver</button>
          </div>
          
          <template v-else>
            <!-- Camera viewfinder container -->
            <div class="relative w-72 h-72 border-4 border-slate-700 rounded-3xl overflow-hidden shadow-2xl bg-black">
              <video ref="videoElement" autoplay playsinline class="w-full h-full object-cover"></video>
              <!-- Square viewfinder helper mask -->
              <div class="absolute inset-4 border-2 border-dashed border-teal-400 rounded-2xl pointer-events-none"></div>
            </div>
            
            <p class="text-xs text-slate-400 text-center">Encuadra el rostro del estudiante dentro del recuadro.</p>
            
            <div class="flex gap-3 w-full max-w-xs mt-2">
              <button @click="captureFromCamera" class="flex-1 py-3 px-5 bg-teal-600 hover:bg-teal-500 text-white font-extrabold rounded-xl text-sm transition">
                📸 Capturar Foto
              </button>
              <button @click="() => { stopCamera(); mode = 'select'; }" class="py-3 px-5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-extrabold rounded-xl text-sm transition">
                Cancelar
              </button>
            </div>
          </template>
        </div>
        
        <!-- STEP 3: Crop Workspace -->
        <div v-if="mode === 'crop'" class="flex flex-col items-center justify-center w-full gap-4">
          
          <!-- Interactive canvas -->
          <div class="relative overflow-hidden rounded-2xl border-4 border-slate-800 bg-slate-950 shadow-2xl cursor-move">
            <canvas ref="workspaceCanvas" 
                    width="480" 
                    height="480"
                    @mousedown="onMouseDown"
                    @mousemove="onMouseMove"
                    @mouseup="onMouseUp"
                    @mouseleave="onMouseUp"
                    @touchstart="onTouchStart"
                    @touchmove="onTouchMove"
                    @touchend="onMouseUp"
                    class="block"></canvas>
          </div>
          
          <div class="w-full max-w-sm space-y-3">
            <div class="flex items-center justify-between text-xs text-slate-400">
              <span>Arrastra para encuadrar</span>
              <span>Zoom</span>
            </div>
            
            <div class="flex items-center gap-4">
              <button @click="adjustZoom(-0.05)" class="p-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-sm text-slate-300">
                ➖
              </button>
              <input type="range" 
                     :min="minZoom" 
                     :max="maxZoom" 
                     step="0.001" 
                     :value="zoom" 
                     @input="onZoomChange"
                     class="flex-1 accent-teal-500 bg-slate-800 h-2 rounded-lg cursor-pointer">
              <button @click="adjustZoom(0.05)" class="p-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-sm text-slate-300">
                ➕
              </button>
            </div>
            
            <div class="flex gap-3 pt-3">
              <button @click="finishCrop" class="flex-1 py-3 px-5 bg-teal-600 hover:bg-teal-500 text-white font-extrabold rounded-xl text-sm shadow-md transition">
                ✓ Recortar y Guardar
              </button>
              <button @click="mode = 'select'" class="py-3 px-5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-extrabold rounded-xl text-sm transition">
                Volver
              </button>
            </div>
          </div>
        </div>
        
      </div>
      
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.2s ease-out forwards;
}

@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
</style>
