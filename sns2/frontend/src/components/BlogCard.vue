<script setup lang="ts">
import { useRouter } from 'vue-router'
import EmojiReactionBar from './EmojiReactionBar.vue'
import QuoteButton from './QuoteButton.vue'
import QuotedPost from './QuotedPost.vue'

interface Blog {
  id: number
  title: string
  content: string
  user: {
    id: number
    username: string
    display_name: string
  }
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

defineProps<{ blog: Blog }>()
const router = useRouter()

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('ja-JP', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<template>
  <article 
    class="card card-hover"
    @click="router.push(`/blog/${blog.id}`)"
  >
    <div class="flex items-start gap-4">
      <!-- Thumbnail/Icon -->
      <div class="flex-shrink-0 w-24 h-24 rounded-lg bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center">
        <i class="pi pi-book text-3xl text-slate-400"></i>
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <h3 class="font-semibold text-gray-900 text-lg mb-1 line-clamp-2">
          {{ blog.title }}
        </h3>

        <p class="text-gray-500 text-sm line-clamp-2 mb-2">
          {{ blog.content }}
        </p>

        <div class="flex items-center gap-3 text-sm text-gray-400">
          <span>{{ blog.user.display_name }}</span>
          <span>{{ formatDate(blog.created_at) }}</span>
        </div>

        <!-- Quoted Post -->
        <div v-if="blog.quoted_posts?.length">
          <QuotedPost 
            v-for="quote in blog.quoted_posts" 
            :key="quote.id" 
            :post="quote" 
          />
        </div>

        <!-- Actions -->
        <div class="mt-3 flex items-center gap-2" @click.stop>
          <QuoteButton :source-post="{ id: blog.id, type: 'blog', title: blog.title, user: blog.user }" />
          <div class="w-px h-4 bg-gray-200"></div>
          <EmojiReactionBar :post-id="blog.id" :reactions="blog.reaction_counts" :user-reactions="blog.user_reactions" />
        </div>
      </div>
    </div>
  </article>
</template>
