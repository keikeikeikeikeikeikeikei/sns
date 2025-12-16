<script setup lang="ts">
import { useRouter } from 'vue-router'
import EmojiReactionBar from './EmojiReactionBar.vue'
import QuoteButton from './QuoteButton.vue'
import QuotedPost from './QuotedPost.vue'

interface Feed {
  id: number
  type: string
  content: string
  image_urls?: string[]
  user: {
    id: number
    username: string
    display_name: string
    avatar_url?: string
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

defineProps<{ feed: Feed }>()
const router = useRouter()

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  
  if (diff < 60000) return 'たった今'
  if (diff < 3600000) return `${Math.floor(diff / 60000)}分前`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)}時間前`
  
  return date.toLocaleDateString('ja-JP', { month: 'short', day: 'numeric' })
}

const openImage = (url: string) => {
  window.open(url, '_blank')
}
</script>

<template>
  <article 
    class="card card-hover"
    @click="router.push(`/feed/${feed.id}`)"
  >
    <div class="flex gap-3">
      <!-- Avatar -->
      <div class="flex-shrink-0">
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-medium">
          {{ feed.user.display_name?.charAt(0) || feed.user.username.charAt(0) }}
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-1">
          <span class="font-medium text-gray-900 truncate">
            {{ feed.user.display_name }}
          </span>
          <span class="text-gray-400 text-sm">
            @{{ feed.user.username }}
          </span>
          <span class="text-gray-300">·</span>
          <span class="text-gray-400 text-sm">
            {{ formatDate(feed.created_at) }}
          </span>
        </div>

        <p class="text-gray-800 whitespace-pre-wrap break-words">
          {{ feed.content }}
        </p>

        <!-- Images -->
        <div v-if="feed.image_urls?.length" class="mt-3 grid gap-2" :class="feed.image_urls.length === 1 ? 'grid-cols-1' : 'grid-cols-2'">
          <img
            v-for="(url, index) in feed.image_urls"
            :key="index"
            :src="url"
            class="w-full rounded-lg object-cover cursor-pointer hover:opacity-90 transition-opacity"
            :class="feed.image_urls.length === 1 ? 'max-h-80' : 'h-32'"
            @click.stop="openImage(url)"
            alt="Post image"
          >
        </div>

        <!-- Quoted Post -->
        <div v-if="feed.quoted_posts?.length">
          <QuotedPost 
            v-for="quote in feed.quoted_posts" 
            :key="quote.id" 
            :post="quote" 
          />
        </div>

        <!-- Actions (Quote + Reactions) -->
        <div class="mt-3 flex items-center gap-2" @click.stop>
          <QuoteButton :source-post="{ id: feed.id, type: 'feed', content: feed.content, user: feed.user }" />
          <div class="w-px h-4 bg-gray-200"></div>
          <EmojiReactionBar :post-id="feed.id" :reactions="feed.reaction_counts" :user-reactions="feed.user_reactions" />
        </div>
      </div>
    </div>
  </article>
</template>


