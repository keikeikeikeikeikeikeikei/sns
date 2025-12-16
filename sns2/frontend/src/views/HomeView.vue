<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axiosInstance from '@/api/axios-instance'
import FeedTab from '@/components/FeedTab.vue'
import QaTab from '@/components/QaTab.vue'
import BlogTab from '@/components/BlogTab.vue'

const router = useRouter()
const activeTab = ref<'feed' | 'qa' | 'blog'>('feed')

const tabs = [
  { id: 'feed', label: 'つぶやき', icon: 'pi pi-comment' },
  { id: 'qa', label: 'Q&A', icon: 'pi pi-question-circle' },
  { id: 'blog', label: 'ブログ', icon: 'pi pi-book' },
] as const

const currentUser = ref<{ username: string; display_name: string } | null>(null)

// Fetch current user
axiosInstance.get('/me').then(res => {
  currentUser.value = res.data
}).catch(() => {
  // Ignore - will redirect to login if 401
})

const logout = () => {
  localStorage.removeItem('token')
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200">
      <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
        <h1 class="text-xl font-bold bg-gradient-to-r from-primary-500 to-primary-700 bg-clip-text text-transparent">
          SNS_2A
        </h1>
        
        <div class="flex items-center gap-3">
          <!-- Search Button -->
          <button @click="router.push('/search')" class="btn btn-ghost p-2" title="検索">
            <i class="pi pi-search text-gray-600"></i>
          </button>

          <span v-if="currentUser" class="text-sm text-gray-600">
            {{ currentUser.display_name }}
          </span>
          <button @click="logout" class="btn btn-ghost text-sm">
            <i class="pi pi-sign-out mr-1"></i>
            ログアウト
          </button>
        </div>
      </div>
    </header>

    <!-- Tab Navigation -->
    <nav class="sticky top-[57px] z-40 bg-white border-b border-gray-200">
      <div class="max-w-4xl mx-auto px-4">
        <div class="flex gap-1">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'flex items-center gap-2 px-4 py-3 text-sm font-medium transition-all border-b-2 -mb-px',
              activeTab === tab.id
                ? 'border-primary-500 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <i :class="tab.icon"></i>
            {{ tab.label }}
          </button>
        </div>
      </div>
    </nav>

    <!-- Content -->
    <main class="max-w-4xl mx-auto px-4 py-6">
      <Transition name="fade" mode="out-in">
        <FeedTab v-if="activeTab === 'feed'" />
        <QaTab v-else-if="activeTab === 'qa'" />
        <BlogTab v-else-if="activeTab === 'blog'" />
      </Transition>
    </main>
  </div>
</template>
