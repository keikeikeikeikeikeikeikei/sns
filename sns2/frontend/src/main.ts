import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import { VueQueryPlugin, QueryClient } from '@tanstack/vue-query'
import PrimeVue from 'primevue/config'
import 'primeicons/primeicons.css'
import './style.css'
import App from './App.vue'

// Routes
const routes = [
    {
        path: '/',
        name: 'Home',
        component: () => import('./views/HomeView.vue'),
    },
    {
        path: '/login',
        name: 'Login',
        component: () => import('./views/LoginView.vue'),
    },
    {
        path: '/register',
        name: 'Register',
        component: () => import('./views/RegisterView.vue'),
    },
    {
        path: '/feed/:id',
        name: 'FeedDetail',
        component: () => import('./views/FeedDetailView.vue'),
    },
    {
        path: '/qa/:id',
        name: 'QaDetail',
        component: () => import('./views/QaDetailView.vue'),
    },
    {
        path: '/blog/:id',
        name: 'BlogDetail',
        component: () => import('./views/BlogDetailView.vue'),
    },
    {
        path: '/qa/new',
        name: 'NewQuestion',
        component: () => import('./views/NewQuestionView.vue'),
    },
    {
        path: '/blog/new',
        name: 'NewBlog',
        component: () => import('./views/NewBlogView.vue'),
    },
    {
        path: '/search',
        name: 'Search',
        component: () => import('./views/SearchView.vue'),
    },
]

const router = createRouter({
    history: createWebHistory('/sns_2a/'),
    routes,
})

// Auth guard
router.beforeEach((to, _from, next) => {
    const publicPages = ['/login', '/register']
    const authRequired = !publicPages.includes(to.path)
    const token = localStorage.getItem('token')

    if (authRequired && !token) {
        return next('/login')
    }
    next()
})

// Query client
const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 1000 * 60, // 1 minute
            retry: 1,
        },
    },
})

const app = createApp(App)

app.use(router)
app.use(VueQueryPlugin, { queryClient })
app.use(PrimeVue)

app.mount('#app')
