<script setup lang="ts">
import { ref, computed } from 'vue'
import axiosInstance from '@/api/axios-instance'

const props = defineProps<{
  maxImages?: number
}>()

const emit = defineEmits<{
  (e: 'uploaded', urls: string[]): void
}>()

const maxImages = computed(() => props.maxImages ?? 4)
const images = ref<string[]>([])
const uploading = ref(false)
const dragOver = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

const handleFiles = async (files: FileList | null) => {
  if (!files || files.length === 0) return
  if (images.value.length >= maxImages.value) return

  uploading.value = true

  try {
    const formData = new FormData()
    const remaining = maxImages.value - images.value.length
    
    for (let i = 0; i < Math.min(files.length, remaining); i++) {
      formData.append('images[]', files[i])
    }

    const response = await axiosInstance.post('/upload/multiple', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (response.data.urls) {
      images.value = [...images.value, ...response.data.urls]
      emit('uploaded', images.value)
    }
  } catch (error: any) {
    console.error('Upload failed:', error)
    alert(error.response?.data?.error || 'アップロードに失敗しました')
  } finally {
    uploading.value = false
  }
}

const handleDrop = (e: DragEvent) => {
  e.preventDefault()
  dragOver.value = false
  handleFiles(e.dataTransfer?.files || null)
}

const handleDragOver = (e: DragEvent) => {
  e.preventDefault()
  dragOver.value = true
}

const handleDragLeave = () => {
  dragOver.value = false
}

const openFilePicker = () => {
  fileInput.value?.click()
}

const handleFileSelect = (e: Event) => {
  const target = e.target as HTMLInputElement
  handleFiles(target.files)
  target.value = ''
}

const removeImage = (index: number) => {
  images.value.splice(index, 1)
  emit('uploaded', images.value)
}

const clear = () => {
  images.value = []
}

defineExpose({ clear, images })
</script>

<template>
  <div class="space-y-3">
    <!-- Hidden file input -->
    <input
      ref="fileInput"
      type="file"
      accept="image/jpeg,image/png,image/gif,image/webp"
      multiple
      class="hidden"
      @change="handleFileSelect"
    >

    <!-- Uploaded Images Preview -->
    <div v-if="images.length" class="flex flex-wrap gap-2">
      <div
        v-for="(url, index) in images"
        :key="url"
        class="relative group"
      >
        <img
          :src="url"
          class="w-20 h-20 object-cover rounded-lg border border-gray-200"
          alt="Uploaded image"
        >
        <button
          @click="removeImage(index)"
          class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
        >
          <i class="pi pi-times text-xs"></i>
        </button>
      </div>
    </div>

    <!-- Drop Zone -->
    <div
      v-if="images.length < maxImages"
      @drop="handleDrop"
      @dragover="handleDragOver"
      @dragleave="handleDragLeave"
      @click="openFilePicker"
      :class="[
        'border-2 border-dashed rounded-lg p-4 text-center cursor-pointer transition-colors',
        dragOver ? 'border-primary-500 bg-primary-50' : 'border-gray-300 hover:border-gray-400'
      ]"
    >
      <div v-if="uploading" class="text-gray-500">
        <i class="pi pi-spin pi-spinner text-xl"></i>
        <p class="text-sm mt-1">アップロード中...</p>
      </div>
      <div v-else>
        <i class="pi pi-image text-2xl text-gray-400"></i>
        <p class="text-sm text-gray-500 mt-1">
          クリックまたはドラッグ&ドロップで画像を追加
        </p>
        <p class="text-xs text-gray-400 mt-1">
          JPEG, PNG, GIF, WebP (最大5MB, {{ images.length }}/{{ maxImages }}枚)
        </p>
      </div>
    </div>
  </div>
</template>
