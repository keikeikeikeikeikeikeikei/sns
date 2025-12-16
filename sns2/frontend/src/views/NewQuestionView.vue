<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import axiosInstance from '@/api/axios-instance'

const router = useRouter()
const queryClient = useQueryClient()
const title = ref('')
const content = ref('')
const error = ref('')

const mutation = useMutation({
  mutationFn: async () => {
    const res = await axiosInstance.post('/qa', {
      title: title.value,
      content: content.value,
    })
    return res.data
  },
  onSuccess: (data) => {
    queryClient.invalidateQueries({ queryKey: ['qa'] })
    router.push(`/qa/${data.id}`)
  },
  onError: (e: any) => {
    error.value = e.response?.data?.errors?.[0] || '投稿に失敗しました'
  },
})

const submit = () => {
  error.value = ''
  if (title.value.trim() && content.value.trim()) {
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
          <h1 class="text-lg font-semibold">質問を投稿</h1>
        </div>
        <button
          @click="submit"
          class="btn btn-primary"
          :disabled="!title.trim() || !content.trim() || mutation.isPending.value"
        >
          <i v-if="mutation.isPending.value" class="pi pi-spin pi-spinner mr-2"></i>
          投稿する
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
            <label class="form-label">タイトル</label>
            <input
              v-model="title"
              type="text"
              class="form-input"
              placeholder="質問のタイトルを入力"
              maxlength="255"
            >
          </div>

          <div>
            <label class="form-label">詳細</label>
            <textarea
              v-model="content"
              class="w-full p-3 border border-gray-200 rounded-lg resize-none focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none min-h-[200px]"
              placeholder="質問の詳細を入力してください..."
            ></textarea>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
