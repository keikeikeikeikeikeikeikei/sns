<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axiosInstance from '@/api/axios-instance'

const router = useRouter()
const username = ref('')
const email = ref('')
const password = ref('')
const displayName = ref('')
const errors = ref<string[]>([])
const loading = ref(false)

const register = async () => {
  errors.value = []
  loading.value = true
  
  try {
    const res = await axiosInstance.post('/auth/register', {
      username: username.value,
      email: email.value,
      password: password.value,
      display_name: displayName.value || username.value,
    })
    
    localStorage.setItem('token', res.data.token)
    router.push('/')
  } catch (e: any) {
    if (e.response?.data?.errors) {
      errors.value = e.response.data.errors
    } else {
      errors.value = [e.response?.data?.error || '登録に失敗しました']
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-50 to-primary-100 px-4">
    <div class="w-full max-w-md">
      <div class="card p-8">
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold bg-gradient-to-r from-primary-500 to-primary-700 bg-clip-text text-transparent">
            SNS
          </h1>
          <p class="text-gray-500 mt-2">新規アカウント登録</p>
        </div>

        <form @submit.prevent="register" class="space-y-4">
          <div>
            <label class="form-label">ユーザー名</label>
            <input
              v-model="username"
              type="text"
              class="form-input"
              placeholder="username"
              maxlength="50"
              required
            >
          </div>

          <div>
            <label class="form-label">表示名</label>
            <input
              v-model="displayName"
              type="text"
              class="form-input"
              placeholder="表示名（省略可）"
              maxlength="255"
            >
          </div>

          <div>
            <label class="form-label">メールアドレス</label>
            <input
              v-model="email"
              type="email"
              class="form-input"
              placeholder="email@example.com"
              required
            >
          </div>

          <div>
            <label class="form-label">パスワード</label>
            <input
              v-model="password"
              type="password"
              class="form-input"
              placeholder="8文字以上"
              minlength="8"
              required
            >
          </div>

          <div v-if="errors.length" class="p-3 bg-red-50 text-red-600 text-sm rounded-lg">
            <ul class="list-disc list-inside">
              <li v-for="err in errors" :key="err">{{ err }}</li>
            </ul>
          </div>

          <button
            type="submit"
            class="btn btn-primary w-full py-3"
            :disabled="loading"
          >
            <i v-if="loading" class="pi pi-spin pi-spinner mr-2"></i>
            登録
          </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
          既にアカウントをお持ちの方は
          <router-link to="/login" class="text-primary-600 hover:underline font-medium">
            ログイン
          </router-link>
        </p>
      </div>
    </div>
  </div>
</template>
