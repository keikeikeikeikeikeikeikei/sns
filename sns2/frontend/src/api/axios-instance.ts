import axios, { type AxiosRequestConfig } from 'axios'

export const axiosInstance = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
    },
})

// Add auth token to requests
axiosInstance.interceptors.request.use((config) => {
    const token = localStorage.getItem('token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

// Handle 401 responses
axiosInstance.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token')
            window.location.href = '/login'
        }
        return Promise.reject(error)
    }
)

// Custom instance for Orval
export const customInstance = <T>(config: AxiosRequestConfig): Promise<T> => {
    const source = axios.CancelToken.source()
    const promise = axiosInstance({
        ...config,
        cancelToken: source.token,
    }).then(({ data }) => data)

    // @ts-expect-error - Adding cancel method for query cancellation
    promise.cancel = () => {
        source.cancel('Query was cancelled')
    }

    return promise
}

export default axiosInstance
