<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axiosInstance from '@/api/axios-instance'

const router = useRouter()
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const login = async () => {
  error.value = ''
  loading.value = true
  
  try {
    const res = await axiosInstance.post('/auth/login', {
      email: email.value,
      password: password.value,
    })
    
    localStorage.setItem('token', res.data.token)
    router.push('/')
  } catch (e: any) {
    error.value = e.response?.data?.error || 'ログインに失敗しました'
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
          <p class="text-gray-500 mt-2">アカウントにログイン</p>
        </div>

        <form @submit.prevent="login" class="space-y-4">
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
              placeholder="••••••••"
              required
            >
          </div>

          <div v-if="error" class="p-3 bg-red-50 text-red-600 text-sm rounded-lg">
            {{ error }}
          </div>

          <button
            type="submit"
            class="btn btn-primary w-full py-3"
            :disabled="loading"
          >
            <i v-if="loading" class="pi pi-spin pi-spinner mr-2"></i>
            ログイン
          </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
          アカウントをお持ちでない方は
          <router-link to="/register" class="text-primary-600 hover:underline font-medium">
            新規登録
          </router-link>
        </p>
      </div>
    </div>
  </div>
</template>
