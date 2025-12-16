<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import axiosInstance from '@/api/axios-instance'
import EmojiReactionBar from '@/components/EmojiReactionBar.vue'
import QuoteButton from '@/components/QuoteButton.vue'
import QuotedPost from '@/components/QuotedPost.vue'

const route = useRoute()
const router = useRouter()
const queryClient = useQueryClient()
const qaId = route.params.id as string

const answerContent = ref('')

const { data: question, isLoading } = useQuery({
  queryKey: ['qa', qaId],
  queryFn: async () => {
    const res = await axiosInstance.get(`/qa/${qaId}`)
    return res.data
  },
})

const answerMutation = useMutation({
  mutationFn: async (content: string) => {
    await axiosInstance.post(`/qa/${qaId}/answers`, { content })
  },
  onSuccess: () => {
    answerContent.value = ''
    queryClient.invalidateQueries({ queryKey: ['qa', qaId] })
  },
})

const bestAnswerMutation = useMutation({
  mutationFn: async (answerId: number) => {
    await axiosInstance.put(`/qa/${qaId}/best-answer`, { answer_id: answerId })
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['qa', qaId] })
  },
})

const submitAnswer = () => {
  if (answerContent.value.trim()) {
    answerMutation.mutate(answerContent.value)
  }
}

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('ja-JP', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200">
      <div class="max-w-4xl mx-auto px-4 py-3 flex items-center gap-4">
        <button @click="router.back()" class="btn btn-ghost p-2">
          <i class="pi pi-arrow-left"></i>
        </button>
        <h1 class="text-lg font-semibold">Q&A</h1>
      </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-6">
      <div v-if="isLoading" class="text-center py-8">
        <i class="pi pi-spin pi-spinner text-2xl text-gray-400"></i>
      </div>

      <div v-else-if="question" class="space-y-6">
        <!-- Question -->
        <article class="card">
          <div class="flex items-start gap-3 mb-4">
            <span 
              :class="[
                'px-2 py-1 rounded text-xs font-medium',
                question.status === 'resolved' 
                  ? 'bg-green-100 text-green-700' 
                  : 'bg-orange-100 text-orange-700'
              ]"
            >
              {{ question.status === 'resolved' ? '解決済み' : '受付中' }}
            </span>
          </div>

          <h2 class="text-xl font-bold text-gray-900 mb-4">{{ question.title }}</h2>

          <div class="prose prose-slate max-w-none mb-4 whitespace-pre-wrap">
            {{ question.content }}
          </div>
          
          <!-- Quoted Post -->
          <div v-if="question.quoted_posts?.length" class="mb-4">
            <QuotedPost 
              v-for="quote in question.quoted_posts" 
              :key="quote.id" 
              :post="quote" 
            />
          </div>

          <div class="flex items-center gap-3 text-sm text-gray-500 border-t border-gray-100 pt-4">
            <span>{{ question.user.display_name }}</span>
            <span>{{ formatDate(question.created_at) }}</span>
          </div>

          <div class="mt-4 pt-4 border-t border-gray-100 flex items-center gap-2">
            <QuoteButton :source-post="{ id: question.id, type: 'qa', title: question.title, user: question.user }" />
            <div class="w-px h-4 bg-gray-200"></div>
            <EmojiReactionBar :post-id="question.id" :reactions="question.reaction_counts" :user-reactions="question.user_reactions" />
          </div>
        </article>

        <!-- Answers -->
        <div class="space-y-4">
          <h3 class="font-semibold text-gray-700">回答 ({{ question.answers?.length || 0 }}件)</h3>

          <div v-for="answer in question.answers" :key="answer.id" class="card">
            <div v-if="answer.is_best_answer" class="flex items-center gap-2 text-green-600 font-medium mb-3">
              <i class="pi pi-check-circle"></i>
              ベストアンサー
            </div>

            <p class="text-gray-800 whitespace-pre-wrap mb-4">{{ answer.content }}</p>

            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-500">
                {{ answer.user.display_name }} · {{ formatDate(answer.created_at) }}
              </div>

              <button
                v-if="question.status === 'open' && !answer.is_best_answer"
                @click="bestAnswerMutation.mutate(answer.id)"
                class="btn btn-secondary text-sm"
                :disabled="bestAnswerMutation.isPending.value"
              >
                ベストアンサーに選ぶ
              </button>
            </div>

            <div class="mt-3">
              <EmojiReactionBar :post-id="answer.id" :reactions="answer.reaction_counts" :user-reactions="answer.user_reactions" />
            </div>
          </div>
        </div>

        <!-- Answer Form -->
        <div v-if="question.status === 'open'" class="card">
          <h3 class="font-semibold text-gray-700 mb-3">回答を投稿</h3>
          <form @submit.prevent="submitAnswer">
            <textarea
              v-model="answerContent"
              class="w-full p-3 border border-gray-200 rounded-lg resize-none focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none"
              rows="4"
              placeholder="回答を入力..."
            ></textarea>
            <div class="flex justify-end mt-3">
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="!answerContent.trim() || answerMutation.isPending.value"
              >
                回答する
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</template>
