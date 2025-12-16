<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import axiosInstance from '@/api/axios-instance'
import FeedCard from '@/components/FeedCard.vue'
import ImageUploader from '@/components/ImageUploader.vue'

const queryClient = useQueryClient()
const newContent = ref('')
const imageUrls = ref<string[]>([])
const imageUploader = ref<InstanceType<typeof ImageUploader> | null>(null)
const currentPage = ref(1)
const MAX_LENGTH = 150
const PER_PAGE = 20

const { data: feedsResponse, isLoading, isFetching } = useQuery({
  queryKey: ['feeds', currentPage],
  queryFn: async () => {
    const res = await axiosInstance.get('/feeds', {
      params: { page: currentPage.value, per_page: PER_PAGE }
    })
    return res.data
  },
})

const feeds = computed(() => feedsResponse.value?.data ?? [])
const meta = computed(() => feedsResponse.value?.meta ?? { current_page: 1, last_page: 1, total: 0 })
const hasMore = computed(() => meta.value.current_page < meta.value.last_page)

const postMutation = useMutation({
  mutationFn: async (data: { content: string; image_urls: string[] }) => {
    const res = await axiosInstance.post('/feeds', data)
    return res.data
  },
  onSuccess: () => {
    newContent.value = ''
    imageUrls.value = []
    imageUploader.value?.clear()
    currentPage.value = 1
    queryClient.invalidateQueries({ queryKey: ['feeds'] })
  },
})

const submitPost = () => {
  if (newContent.value.trim() && newContent.value.length <= MAX_LENGTH) {
    postMutation.mutate({
      content: newContent.value,
      image_urls: imageUrls.value,
    })
  }
}

const loadMore = () => {
  if (hasMore.value && !isFetching.value) {
    currentPage.value++
  }
}

const handleImagesUploaded = (urls: string[]) => {
  imageUrls.value = urls
}

const remainingChars = () => MAX_LENGTH - newContent.value.length
</script>

<template>
  <div class="space-y-6">
    <!-- Post Form -->
    <div class="card">
      <form @submit.prevent="submitPost">
        <textarea
          v-model="newContent"
          class="w-full p-3 border border-gray-200 rounded-lg resize-none focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none"
          rows="3"
          placeholder="いまどうしてる？"
          :maxlength="MAX_LENGTH"
        ></textarea>
        
        <!-- Image Uploader -->
        <div class="mt-3">
          <ImageUploader
            ref="imageUploader"
            :max-images="4"
            @uploaded="handleImagesUploaded"
          />
        </div>
        
        <div class="flex items-center justify-between mt-3">
          <span 
            :class="[
              'text-sm',
              remainingChars() < 20 ? 'text-orange-500' : 'text-gray-400',
              remainingChars() < 0 ? 'text-red-500' : ''
            ]"
          >
            {{ remainingChars() }}
          </span>
          
          <button
            type="submit"
            class="btn btn-primary"
            :disabled="!newContent.trim() || remainingChars() < 0 || postMutation.isPending.value"
          >
            <i v-if="postMutation.isPending.value" class="pi pi-spin pi-spinner mr-2"></i>
            投稿
          </button>
        </div>
      </form>
    </div>

    <!-- Feed List -->
    <div v-if="isLoading" class="text-center py-8 text-gray-500">
      <i class="pi pi-spin pi-spinner text-2xl"></i>
    </div>

    <div v-else-if="feeds.length" class="space-y-4">
      <FeedCard
        v-for="feed in feeds"
        :key="feed.id"
        :feed="feed"
      />

      <!-- Pagination Info -->
      <div class="text-center text-sm text-gray-500">
        {{ meta.total }} 件中 {{ feeds.length }} 件表示
      </div>

      <!-- Load More Button -->
      <div v-if="hasMore" class="text-center">
        <button
          @click="loadMore"
          class="btn btn-secondary"
          :disabled="isFetching"
        >
          <i v-if="isFetching" class="pi pi-spin pi-spinner mr-2"></i>
          もっと見る
        </button>
      </div>
    </div>

    <div v-else class="text-center py-8 text-gray-500">
      まだ投稿がありません。最初の投稿をしてみましょう！
    </div>
  </div>
</template>


