<script setup lang="ts">
import { useRouter } from 'vue-router'

interface QuotedPostData {
  id: number
  type: string
  title?: string
  content: string
  user: {
    id: number
    username: string
    display_name: string
  }
}

const props = defineProps<{ post: QuotedPostData }>()
const router = useRouter()

const navigateToPost = () => {
  let path = ''
  switch (props.post.type) {
    case 'feed':
      path = `/feed/${props.post.id}`
      break
    case 'qa':
      path = `/qa/${props.post.id}`
      break
    case 'blog':
      path = `/blog/${props.post.id}`
      break
  }
  if (path) {
    router.push(path)
  }
}
</script>

<template>
  <div 
    class="mt-2 border border-gray-200 rounded-lg p-3 hover:bg-gray-50 cursor-pointer transition-colors"
    @click.stop="navigateToPost"
  >
    <div class="flex items-center gap-2 mb-1">
      <span class="font-medium text-sm text-gray-900">
        {{ post.user.display_name }}
      </span>
      <span class="text-xs text-gray-500">
        @{{ post.user.username }}
      </span>
    </div>

    <!-- Title (for Blog/QA) -->
    <h4 v-if="post.title" class="font-medium text-gray-900 text-sm mb-1 line-clamp-1">
      {{ post.title }}
    </h4>

    <p class="text-gray-600 text-sm line-clamp-3 whitespace-pre-wrap break-words">
      {{ post.content }}
    </p>
  </div>
</template>
