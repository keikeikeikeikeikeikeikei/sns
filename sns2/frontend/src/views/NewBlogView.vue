<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import axiosInstance from '@/api/axios-instance'

const router = useRouter()
const queryClient = useQueryClient()
const title = ref('')
const content = ref('')
const error = ref('')
const MAX_LENGTH = 10000

const remainingChars = computed(() => MAX_LENGTH - content.value.length)

const mutation = useMutation({
  mutationFn: async () => {
    const res = await axiosInstance.post('/blogs', {
      title: title.value,
      content: content.value,
    })
    return res.data
  },
  onSuccess: (data) => {
    queryClient.invalidateQueries({ queryKey: ['blogs'] })
    router.push(`/blog/${data.id}`)
  },
  onError: (e: any) => {
    error.value = e.response?.data?.errors?.[0] || '投稿に失敗しました'
  },
})

const submit = () => {
  error.value = ''
  if (title.value.trim() && content.value.trim() && content.value.length <= MAX_LENGTH) {
    mutation.mutate()
  }
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200">
      <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <button @click="router.back()" class="btn btn-ghost p-2">
            <i class="pi pi-arrow-left"></i>
          </button>
          <h1 class="text-lg font-semibold">ブログを書く</h1>
        </div>
        <button
          @click="submit"
          class="btn btn-primary"
          :disabled="!title.trim() || !content.trim() || remainingChars < 0 || mutation.isPending.value"
        >
          <i v-if="mutation.isPending.value" class="pi pi-spin pi-spinner mr-2"></i>
          公開する
        </button>
      </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-6">
      <div class="card">
        <div v-if="error" class="mb-4 p-3 bg-red-50 text-red-600 text-sm rounded-lg">
          {{ error }}
        </div>

        <div class="space-y-4">
          <div>
            <input
              v-model="title"
              type="text"
              class="w-full text-2xl font-bold border-0 border-b border-gray-200 pb-3 focus:border-primary-500 focus:ring-0 outline-none"
              placeholder="タイトル"
              maxlength="255"
            >
          </div>

          <div>
            <textarea
              v-model="content"
              class="w-full p-0 border-0 resize-none focus:ring-0 outline-none min-h-[400px] leading-relaxed"
              placeholder="本文を書き始める..."
              :maxlength="MAX_LENGTH"
            ></textarea>
            
            <div class="flex justify-end mt-2">
              <span 
                :class="[
                  'text-sm',
                  remainingChars < 500 ? 'text-orange-500' : 'text-gray-400',
                  remainingChars < 0 ? 'text-red-500' : ''
                ]"
              >
                {{ remainingChars.toLocaleString() }} / {{ MAX_LENGTH.toLocaleString() }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
