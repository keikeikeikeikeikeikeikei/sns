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
const blogId = route.params.id as string

const { data: blog, isLoading } = useQuery({
  queryKey: ['blog', blogId],
  queryFn: async () => {
    const res = await axiosInstance.get(`/blogs/${blogId}`)
    return res.data
  },
})

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('ja-JP', { year: 'numeric', month: 'long', day: 'numeric' })
}

const commentContent = ref('')
const queryClient = useQueryClient()

const commentMutation = useMutation({
  mutationFn: async (content: string) => {
    await axiosInstance.post(`/blogs/${blogId}/comments`, { content })
  },
  onSuccess: () => {
    commentContent.value = ''
    queryClient.invalidateQueries({ queryKey: ['blog', blogId] })
  },
})

const submitComment = () => {
  if (commentContent.value.trim()) {
    commentMutation.mutate(commentContent.value)
  }
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200">
      <div class="max-w-4xl mx-auto px-4 py-3 flex items-center gap-4">
        <button @click="router.back()" class="btn btn-ghost p-2">
          <i class="pi pi-arrow-left"></i>
        </button>
        <h1 class="text-lg font-semibold">ブログ</h1>
      </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-6">
      <div v-if="isLoading" class="text-center py-8">
        <i class="pi pi-spin pi-spinner text-2xl text-gray-400"></i>
      </div>

      <article v-else-if="blog" class="card">
        <header class="mb-6">
          <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ blog.title }}</h1>
          
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-medium">
              {{ blog.user.display_name?.charAt(0) || blog.user.username.charAt(0) }}
            </div>
            <div>
              <div class="font-medium text-gray-900">{{ blog.user.display_name }}</div>
              <div class="text-sm text-gray-500">{{ formatDate(blog.created_at) }}</div>
            </div>
          </div>
        </header>

        <div class="prose prose-slate max-w-none whitespace-pre-wrap leading-relaxed mb-6">
          {{ blog.content }}
        </div>

        <!-- Quoted Post -->
        <div v-if="blog.quoted_posts?.length" class="mb-6">
          <QuotedPost 
            v-for="quote in blog.quoted_posts" 
            :key="quote.id" 
            :post="quote" 
          />
        </div>

        <footer class="mt-8 pt-6 border-t border-gray-100 flex items-center gap-2">
          <QuoteButton :source-post="{ id: blog.id, type: 'blog', title: blog.title, user: blog.user }" />
          <div class="w-px h-4 bg-gray-200"></div>
          <EmojiReactionBar :post-id="blog.id" :reactions="blog.reaction_counts" :user-reactions="blog.user_reactions" />
        </footer>
      </article>
    </main>

    <!-- Comments Section (below main card for better width?) or inside? Let's keep inside max-w-4xl but maybe separate card -->
    <section class="max-w-4xl mx-auto px-4 pb-12 space-y-6">
      <h3 class="text-lg font-semibold text-gray-700">コメント ({{ blog?.comments?.length || 0 }}件)</h3>

      <!-- Comment List -->
      <div v-if="blog?.comments" class="space-y-4">
        <div v-for="comment in blog.comments" :key="comment.id" class="card">
          <p class="text-gray-800 whitespace-pre-wrap mb-3">{{ comment.content }}</p>
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">
               {{ comment.user.display_name }} · {{ formatDate(comment.created_at) }}
            </div>
          </div>
          <div class="mt-3">
             <EmojiReactionBar :post-id="comment.id" :reactions="comment.reaction_counts" :user-reactions="comment.user_reactions" />
          </div>
        </div>
      </div>

      <!-- Comment Form -->
      <div class="card">
         <h3 class="font-semibold text-gray-700 mb-3">コメントを投稿</h3>
         <form @submit.prevent="submitComment">
           <textarea
             v-model="commentContent"
             class="w-full p-3 border border-gray-200 rounded-lg resize-none focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none"
             rows="3"
             placeholder="コメントを入力..."
           ></textarea>
           <div class="flex justify-end mt-3">
             <button
               type="submit"
               class="btn btn-primary"
               :disabled="!commentContent.trim() || commentMutation.isPending.value"
             >
               投稿する
             </button>
           </div>
         </form>
      </div>
    </section>

  </div>
</template>
