<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import axiosInstance from '@/api/axios-instance'
import QaCard from '@/components/QaCard.vue'

const router = useRouter()

const { data: questions, isLoading } = useQuery({
  queryKey: ['qa'],
  queryFn: async () => {
    const res = await axiosInstance.get('/qa')
    return res.data
  },
})
</script>

<template>
  <div class="space-y-6">
    <!-- New Question Button -->
    <div class="flex justify-end">
      <button
        @click="router.push('/qa/new')"
        class="btn btn-primary"
      >
        <i class="pi pi-plus mr-2"></i>
        質問する
      </button>
    </div>

    <!-- Questions List -->
    <div v-if="isLoading" class="text-center py-8 text-gray-500">
      <i class="pi pi-spin pi-spinner text-2xl"></i>
    </div>

    <div v-else-if="questions?.data?.length" class="space-y-4">
      <QaCard
        v-for="question in questions.data"
        :key="question.id"
        :question="question"
      />
    </div>

    <div v-else class="text-center py-8 text-gray-500">
      まだ質問がありません。最初の質問を投稿してみましょう！
    </div>
  </div>
</template>
