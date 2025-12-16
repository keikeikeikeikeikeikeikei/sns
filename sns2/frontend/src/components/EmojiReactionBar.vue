<script setup lang="ts">
import { ref, computed } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import axiosInstance from '@/api/axios-instance'
import EmojiPicker from './EmojiPicker.vue'

const props = defineProps<{
  postId: number
  reactions: Record<string, number>
  userReactions?: string[] // Emojis the user has reacted with
}>()

const queryClient = useQueryClient()
const showPicker = ref(false)

const sortedReactions = computed(() => {
  return Object.entries(props.reactions)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 10)
})

const addReaction = useMutation({
  mutationFn: async (emoji: string) => {
    await axiosInstance.post(`/posts/${props.postId}/reactions`, { emoji })
  },
  onSuccess: () => {
    invalidateAll()
    showPicker.value = false
  },
})

const removeReaction = useMutation({
  mutationFn: async (emoji: string) => {
    await axiosInstance.delete(`/posts/${props.postId}/reactions/${encodeURIComponent(emoji)}`)
  },
  onSuccess: () => {
    invalidateAll()
  },
})

const invalidateAll = () => {
  queryClient.invalidateQueries({ queryKey: ['feeds'] })
  queryClient.invalidateQueries({ queryKey: ['qa'] })
  queryClient.invalidateQueries({ queryKey: ['blogs'] })
  queryClient.invalidateQueries({ queryKey: ['feed', props.postId.toString()] })
  queryClient.invalidateQueries({ queryKey: ['blog', props.postId.toString()] })
  queryClient.invalidateQueries({ queryKey: ['qa', props.postId.toString()] })
}

const selectEmoji = (emoji: string) => {
  if (props.userReactions?.includes(emoji)) {
    removeReaction.mutate(emoji)
  } else {
    addReaction.mutate(emoji)
  }
}

const openPicker = () => {
  showPicker.value = true
}
</script>

<template>
  <div class="flex items-center gap-2 flex-wrap">
    <!-- Existing reactions -->
    <button
      v-for="[emoji, count] in sortedReactions"
      :key="emoji"
      @click="selectEmoji(emoji)"
      class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-sm transition-colors border"
      :class="props.userReactions?.includes(emoji) 
        ? 'bg-primary-100 border-primary-300 text-primary-800' 
        : 'bg-gray-100 border-transparent hover:bg-gray-200 text-gray-700'"
    >
      <span>{{ emoji }}</span>
      <span class="font-medium opacity-80">{{ count }}</span>
    </button>

    <!-- Add reaction button -->
    <button
      @click="openPicker"
      class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 transition-colors"
    >
      <i class="pi pi-face-smile text-sm"></i>
    </button>

    <!-- Full Emoji Picker Modal -->
    <EmojiPicker
      :visible="showPicker"
      @select="selectEmoji"
      @close="showPicker = false"
    />
  </div>
</template>
