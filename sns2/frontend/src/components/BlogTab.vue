<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import axiosInstance from '@/api/axios-instance'
import BlogCard from '@/components/BlogCard.vue'

const router = useRouter()

const { data: blogs, isLoading } = useQuery({
  queryKey: ['blogs'],
  queryFn: async () => {
    const res = await axiosInstance.get('/blogs')
    return res.data
  },
})
</script>

<template>
  <div class="space-y-6">
    <!-- New Blog Button -->
    <div class="flex justify-end">
      <button
        @click="router.push('/blog/new')"
        class="btn btn-primary"
      >
        <i class="pi pi-plus mr-2"></i>
        ブログを書く
      </button>
    </div>

    <!-- Blog List -->
    <div v-if="isLoading" class="text-center py-8 text-gray-500">
      <i class="pi pi-spin pi-spinner text-2xl"></i>
    </div>

    <div v-else-if="blogs?.data?.length" class="space-y-4">
      <BlogCard
        v-for="blog in blogs.data"
        :key="blog.id"
        :blog="blog"
      />
    </div>

    <div v-else class="text-center py-8 text-gray-500">
      まだブログがありません。最初のブログを書いてみましょう！
    </div>
  </div>
</template>
