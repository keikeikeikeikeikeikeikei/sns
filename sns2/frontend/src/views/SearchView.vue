<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import axiosInstance from '@/api/axios-instance'

const route = useRoute()
const router = useRouter()
const searchQuery = ref((route.query.q as string) || '')
const searchType = ref((route.query.type as string) || 'all')
const searchInput = ref(searchQuery.value)

const { data: results, isLoading, refetch } = useQuery({
  queryKey: ['search', searchQuery, searchType],
  queryFn: async () => {
    if (!searchQuery.value) return null
    const res = await axiosInstance.get('/search', {
      params: { q: searchQuery.value, type: searchType.value }
    })
    return res.data
  },
  enabled: computed(() => !!searchQuery.value),
})

const submitSearch = () => {
  if (searchInput.value.trim()) {
    searchQuery.value = searchInput.value.trim()
    router.push({ query: { q: searchQuery.value, type: searchType.value } })
    refetch()
  }
}

const changeType = (type: string) => {
  searchType.value = type
  if (searchQuery.value) {
    router.push({ query: { q: searchQuery.value, type } })
    refetch()
  }
}

const navigateToPost = (post: any) => {
  if (post.type === 'feed') {
    router.push(`/feed/${post.id}`)
  } else if (post.type === 'qa') {
    router.push(`/qa/${post.id}`)
  } else if (post.type === 'blog') {
    router.push(`/blog/${post.id}`)
  }
}

const getTypeLabel = (type: string) => {
  switch (type) {
    case 'feed': return 'つぶやき'
    case 'qa': return 'Q&A'
    case 'blog': return 'ブログ'
    default: return type
  }
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200">
      <div class="max-w-4xl mx-auto px-4 py-3 flex items-center gap-4">
        <button @click="router.push('/')" class="btn btn-ghost p-2">
          <i class="pi pi-arrow-left"></i>
        </button>
        <h1 class="text-lg font-semibold">検索</h1>
      </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-6">
      <!-- Search Form -->
      <div class="card mb-6">
        <form @submit.prevent="submitSearch" class="flex gap-2">
          <div class="relative flex-1">
            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input
              v-model="searchInput"
              type="text"
              class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none"
              placeholder="キーワードで検索..."
            >
          </div>
          <button type="submit" class="btn btn-primary">
            検索
          </button>
        </form>

        <!-- Type Filter -->
        <div class="flex gap-2 mt-4">
          <button
            @click="changeType('all')"
            :class="['px-3 py-1 rounded-full text-sm transition-colors', searchType === 'all' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']"
          >
            すべて
          </button>
          <button
            @click="changeType('posts')"
            :class="['px-3 py-1 rounded-full text-sm transition-colors', searchType === 'posts' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']"
          >
            投稿
          </button>
          <button
            @click="changeType('users')"
            :class="['px-3 py-1 rounded-full text-sm transition-colors', searchType === 'users' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']"
          >
            ユーザー
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="isLoading" class="text-center py-8">
        <i class="pi pi-spin pi-spinner text-2xl text-gray-400"></i>
      </div>

      <!-- Results -->
      <div v-else-if="results" class="space-y-6">
        <!-- Posts -->
        <div v-if="results.posts?.data?.length">
          <h2 class="text-sm font-semibold text-gray-500 mb-3">
            投稿 ({{ results.posts.meta.total }}件)
          </h2>
          <div class="space-y-3">
            <div
              v-for="post in results.posts.data"
              :key="post.id"
              @click="navigateToPost(post)"
              class="card card-hover cursor-pointer"
            >
              <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-medium flex-shrink-0">
                  {{ post.user.display_name?.charAt(0) || post.user.username.charAt(0) }}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-600">
                      {{ getTypeLabel(post.type) }}
                    </span>
                    <span class="font-medium text-gray-900">{{ post.user.display_name }}</span>
                  </div>
                  <p v-if="post.title" class="font-medium text-gray-800 mb-1">{{ post.title }}</p>
                  <p class="text-gray-600 text-sm line-clamp-2">{{ post.content }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Users -->
        <div v-if="results.users?.data?.length">
          <h2 class="text-sm font-semibold text-gray-500 mb-3">
            ユーザー ({{ results.users.meta.total }}件)
          </h2>
          <div class="space-y-3">
            <div
              v-for="user in results.users.data"
              :key="user.id"
              class="card card-hover cursor-pointer"
            >
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-medium text-lg">
                  {{ user.display_name?.charAt(0) || user.username.charAt(0) }}
                </div>
                <div>
                  <div class="font-medium text-gray-900">{{ user.display_name }}</div>
                  <div class="text-gray-500 text-sm">@{{ user.username }}</div>
                  <p v-if="user.bio" class="text-gray-600 text-sm mt-1 line-clamp-1">{{ user.bio }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- No results -->
        <div v-if="!results.posts?.data?.length && !results.users?.data?.length" class="text-center py-8 text-gray-500">
          「{{ searchQuery }}」に一致する結果が見つかりませんでした
        </div>
      </div>

      <!-- Initial state -->
      <div v-else-if="!searchQuery" class="text-center py-8 text-gray-500">
        キーワードを入力して検索してください
      </div>
    </main>
  </div>
</template>
