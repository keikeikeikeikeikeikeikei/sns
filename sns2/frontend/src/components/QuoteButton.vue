<script setup lang="ts">
import { ref } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import axiosInstance from '@/api/axios-instance'

interface Post {
  id: number
  type: string
  content?: string
  title?: string
  user: {
    display_name: string
    username: string
  }
}

const props = defineProps<{
  sourcePost: Post
}>()

const queryClient = useQueryClient()
const showModal = ref(false)
const quoteContent = ref('')
const MAX_LENGTH = 150

const quoteMutation = useMutation({
  mutationFn: async (content: string) => {
    const res = await axiosInstance.post(`/posts/${props.sourcePost.id}/quotes`, { content })
    return res.data
  },
  onSuccess: () => {
    showModal.value = false
    quoteContent.value = ''
    queryClient.invalidateQueries({ queryKey: ['feeds'] })
  },
})

const submitQuote = () => {
  if (quoteContent.value.trim() && quoteContent.value.length <= MAX_LENGTH) {
    quoteMutation.mutate(quoteContent.value)
  }
}

const getPreview = () => {
  if (props.sourcePost.type === 'feed') {
    return props.sourcePost.content || ''
  }
  return props.sourcePost.title || ''
}

const remainingChars = () => MAX_LENGTH - quoteContent.value.length
</script>

<template>
  <div>
    <!-- Quote Button -->
    <button
      @click="showModal = true"
      class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors text-sm"
      title="引用"
    >
      <i class="pi pi-replay"></i>
      <span class="hidden sm:inline">引用</span>
    </button>

    <!-- Quote Modal -->
    <Transition name="fade">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
          <!-- Header -->
          <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">引用してつぶやく</h3>
            <button @click="showModal = false" class="p-1 hover:bg-gray-100 rounded-full transition-colors">
              <i class="pi pi-times text-gray-500"></i>
            </button>
          </div>

          <!-- Content -->
          <div class="p-4">
            <!-- Quote textarea -->
            <textarea
              v-model="quoteContent"
              class="w-full p-3 border border-gray-200 rounded-lg resize-none focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none mb-3"
              rows="3"
              placeholder="コメントを追加..."
              :maxlength="MAX_LENGTH"
            ></textarea>

            <div class="flex justify-end mb-4">
              <span 
                :class="[
                  'text-sm',
                  remainingChars() < 20 ? 'text-orange-500' : 'text-gray-400',
                  remainingChars() < 0 ? 'text-red-500' : ''
                ]"
              >
                {{ remainingChars() }}
              </span>
            </div>

            <!-- Quoted post preview -->
            <div class="p-3 rounded-lg border border-gray-200 bg-gray-50">
              <div class="flex items-center gap-2 mb-2 text-sm text-gray-500">
                <span class="font-medium">{{ sourcePost.user.display_name }}</span>
                <span>@{{ sourcePost.user.username }}</span>
              </div>
              <p class="text-gray-700 text-sm line-clamp-3">{{ getPreview() }}</p>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex justify-end gap-2 px-4 py-3 border-t border-gray-100 bg-gray-50">
            <button
              @click="showModal = false"
              class="btn btn-secondary"
            >
              キャンセル
            </button>
            <button
              @click="submitQuote"
              class="btn btn-primary"
              :disabled="!quoteContent.trim() || remainingChars() < 0 || quoteMutation.isPending.value"
            >
              <i v-if="quoteMutation.isPending.value" class="pi pi-spin pi-spinner mr-2"></i>
              引用してつぶやく
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>
