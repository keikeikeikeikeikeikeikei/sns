import { defineConfig } from 'orval'

export default defineConfig({
    sns2: {
        input: {
            target: '../backend/openapi.yaml',
        },
        output: {
            mode: 'tags-split',
            target: './src/api/generated',
            schemas: './src/api/models',
            client: 'vue-query',
            httpClient: 'axios',
            baseUrl: '/api',
            override: {
                mutator: {
                    path: './src/api/axios-instance.ts',
                    name: 'customInstance',
                },
                query: {
                    useQuery: true,
                    useMutation: true,
                },
            },
        },
        hooks: {
            afterAllFilesWrite: 'prettier --write',
        },
    },
})
