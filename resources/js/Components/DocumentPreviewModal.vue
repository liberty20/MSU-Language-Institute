<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto animate-fade-in" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background backdrop -->
      <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="$emit('close')"></div>

      <!-- Spacer to center modal -->
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <!-- Modal panel -->
      <div class="inline-block align-middle bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-150">
        <!-- Header -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-150 flex items-center justify-between">
          <div class="min-w-0 flex-1 pr-4">
            <h3 class="text-sm font-bold text-gray-900 truncate" id="modal-title" :title="document?.filename">
              Preview: {{ document?.filename }}
            </h3>
          </div>
          <div class="flex items-center gap-3">
            <!-- Download option in preview header -->
            <a :href="route('documents.download', { document: document?.id, download: 1 })" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-bold transition shadow-sm">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
              Download
            </a>
            <button @click="$emit('close')" type="button" class="text-gray-400 hover:text-gray-600 font-bold text-xl px-2 py-1 hover:bg-gray-100 rounded-lg transition">
              ✕
            </button>
          </div>
        </div>

        <!-- Body -->
        <div class="bg-white p-6">
          <div v-if="isPdf" class="w-full">
            <iframe :src="previewUrl" class="w-full h-[70vh] rounded-xl border border-gray-200"></iframe>
          </div>
          
          <div v-else-if="isImage" class="w-full flex flex-col items-center animate-scale-up">
            <!-- Zoom Controls -->
            <div class="flex items-center gap-3 mb-4 bg-gray-100 px-4 py-1.5 rounded-full border border-gray-200 shadow-sm">
              <button @click="zoomOut" class="w-8 h-8 rounded-full bg-white hover:bg-gray-50 border border-gray-300 flex items-center justify-center font-bold text-gray-700 transition" title="Zoom Out">-</button>
              <span class="text-xs font-bold text-gray-600 w-12 text-center">{{ Math.round(scale * 100) }}%</span>
              <button @click="zoomIn" class="w-8 h-8 rounded-full bg-white hover:bg-gray-50 border border-gray-300 flex items-center justify-center font-bold text-gray-700 transition" title="Zoom In">+</button>
              <button @click="resetZoom" class="px-3 py-1 rounded bg-white hover:bg-gray-50 border border-gray-300 text-xs font-bold text-gray-600 transition ml-2">Reset</button>
            </div>
            
            <!-- Image Container -->
            <div class="w-full overflow-auto border border-gray-150 rounded-xl p-4 flex justify-center items-center h-[60vh] bg-gray-50 relative">
              <img :src="previewUrl" 
                   :style="{ transform: `scale(${scale})`, transformOrigin: 'center center', transition: 'transform 0.1s ease-in-out' }" 
                   class="max-h-full max-w-full object-contain" />
            </div>
          </div>
          
          <div v-else class="text-center py-16 px-4 bg-gray-50 border border-dashed border-gray-250 rounded-2xl">
            <div class="w-16 h-16 bg-amber-100 text-amber-700 rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h4 class="text-base font-bold text-gray-800 mb-2">Preview Not Supported for This File Type</h4>
            <p class="text-sm text-gray-500 max-w-md mx-auto mb-6">
              Inline browser previews are only supported for PDF files and images. You can use the download button to save and view this document on your device.
            </p>
            <a :href="route('documents.download', { document: document?.id, download: 1 })" 
               class="inline-flex items-center gap-2 bg-[#0a1f44] hover:bg-[#152a4d] text-white px-6 py-2.5 rounded-xl text-xs font-bold transition shadow-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
              <span>Download Document</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  show: Boolean,
  document: Object
});

const emit = defineEmits(['close']);

const scale = ref(1);

// Reset scale when document changes or modal opens/closes
watch(() => props.document, () => {
  scale.value = 1;
});
watch(() => props.show, () => {
  scale.value = 1;
});

const previewUrl = computed(() => {
  if (!props.document) return '';
  return route('documents.download', props.document.id);
});

const isPdf = computed(() => {
  if (!props.document) return false;
  const ext = props.document.filename?.split('.').pop()?.toLowerCase();
  return ext === 'pdf' || props.document.mime_type === 'application/pdf';
});

const isImage = computed(() => {
  if (!props.document) return false;
  const ext = props.document.filename?.split('.').pop()?.toLowerCase();
  const imageExts = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'];
  return imageExts.includes(ext) || props.document.mime_type?.startsWith('image/');
});

const zoomIn = () => {
  if (scale.value < 3) {
    scale.value = parseFloat((scale.value + 0.25).toFixed(2));
  }
};

const zoomOut = () => {
  if (scale.value > 0.5) {
    scale.value = parseFloat((scale.value - 0.25).toFixed(2));
  }
};

const resetZoom = () => {
  scale.value = 1;
};
</script>
