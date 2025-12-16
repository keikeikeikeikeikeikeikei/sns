<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import axiosInstance from '@/api/axios-instance'
import EmojiReactionBar from '@/components/EmojiReactionBar.vue'
import QuoteButton from '@/components/QuoteButton.vue'
import QuotedPost from '@/components/QuotedPost.vue'

const route = useRoute()
const router = useRouter()
const feedId = route.params.id as string

const { data: feed, isLoading } = useQuery({
  queryKey: ['feed', feedId],
  queryFn: async () => {
    const res = await axiosInstance.get(`/feeds/${feedId}`)
    return res.data
  },
})

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleString('ja-JP', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const openImage = (url: string) => {
  window.open(url, '_blank')
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200">
      <div class="max-w-4xl mx-auto px-4 py-3 flex items-center gap-4">
        <button @click="router.back()" class="btn btn-ghost p-2">
          <i class="pi pi-arrow-left"></i>
        </button>
        <h1 class="text-lg font-semibold">つぶやき</h1>
      </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-6">
      <div v-if="isLoading" class="text-center py-8">
        <i class="pi pi-spin pi-spinner text-2xl text-gray-400"></i>
      </div>

      <article v-else-if="feed" class="card">
        <div class="flex gap-3 mb-4">
          <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-medium text-lg">
            {{ feed.user.display_name?.charAt(0) || feed.user.username.charAt(0) }}
          </div>
          <div>
            <div class="font-semibold text-gray-900">{{ feed.user.display_name }}</div>
            <div class="text-gray-500 text-sm">@{{ feed.user.username }}</div>
          </div>
        </div>

        <p class="text-xl text-gray-800 whitespace-pre-wrap break-words mb-4">
          {{ feed.content }}
        </p>

        <!-- Images -->
        <div v-if="feed.image_urls?.length" class="mb-4 grid gap-2" :class="feed.image_urls.length === 1 ? 'grid-cols-1' : 'grid-cols-2'">
          <img
            v-for="(url, index) in feed.image_urls"
            :key="index"
            :src="url"
            class="w-full rounded-lg object-cover cursor-pointer hover:opacity-90 transition-opacity"
            :class="feed.image_urls.length === 1 ? 'max-h-96' : 'h-48'"
            @click.stop="openImage(url)"
            alt="Post image"
          >
        </div>

        <!-- Quoted Post -->
        <div v-if="feed.quoted_posts?.length" class="mb-4">
          <QuotedPost 
            v-for="quote in feed.quoted_posts" 
            :key="quote.id" 
            :post="quote" 
          />
        </div>

        <div class="text-gray-500 text-sm border-t border-gray-100 pt-4">
          {{ formatDate(feed.created_at) }}
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center gap-2">
          <QuoteButton :source-post="{ id: feed.id, type: 'feed', content: feed.content, user: feed.user }" />
          <div class="w-px h-4 bg-gray-200"></div>
          <EmojiReactionBar :post-id="feed.id" :reactions="feed.reaction_counts" :user-reactions="feed.user_reactions" />
        </div>
      </article>
    </main>
  </div>
</template>
