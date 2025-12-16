<script setup lang="ts">
import { useRouter } from 'vue-router'
import EmojiReactionBar from './EmojiReactionBar.vue'
import QuoteButton from './QuoteButton.vue'
import QuotedPost from './QuotedPost.vue'

interface Question {
  id: number
  title: string
  content: string
  status: 'open' | 'resolved'
  user: {
    id: number
    username: string
    display_name: string
  }
  answer_count: number
  reaction_counts: Record<string, number>
  user_reactions?: string[]
  quoted_posts?: Array<{
    id: number
    type: string
    title?: string
    content: string
    user: {
      id: number
      username: string
      display_name: string
    }
  }>
  created_at: string
}

defineProps<{ question: Question }>()
const router = useRouter()

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('ja-JP', { month: 'short', day: 'numeric' })
}
</script>

<template>
  <article 
    class="card card-hover"
    @click="router.push(`/qa/${question.id}`)"
  >
    <div class="flex items-start gap-3">
      <!-- Status Badge -->
      <div 
        :class="[
          'flex-shrink-0 px-2 py-1 rounded text-xs font-medium',
          question.status === 'resolved' 
            ? 'bg-green-100 text-green-700' 
            : 'bg-orange-100 text-orange-700'
        ]"
      >
        {{ question.status === 'resolved' ? '解決済み' : '受付中' }}
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <h3 class="font-medium text-gray-900 mb-1 line-clamp-2">
          {{ question.title }}
        </h3>

        <div class="flex items-center gap-3 text-sm text-gray-500">
          <span>{{ question.user.display_name }}</span>
          <span>{{ formatDate(question.created_at) }}</span>
          <span class="flex items-center gap-1">
            <i class="pi pi-comments text-xs"></i>
            {{ question.answer_count }}件の回答
          </span>
        </div>

        <!-- Quoted Post -->
        <div v-if="question.quoted_posts?.length">
          <QuotedPost 
            v-for="quote in question.quoted_posts" 
            :key="quote.id" 
            :post="quote" 
          />
        </div>

        <!-- Actions -->
        <div class="mt-3 flex items-center gap-2" @click.stop>
          <QuoteButton :source-post="{ id: question.id, type: 'qa', title: question.title, user: question.user }" />
          <div class="w-px h-4 bg-gray-200"></div>
          <EmojiReactionBar :post-id="question.id" :reactions="question.reaction_counts" :user-reactions="question.user_reactions" />
        </div>
      </div>
    </div>
  </article>
</template>
