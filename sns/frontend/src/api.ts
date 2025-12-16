const API_BASE = 'http://localhost:8000/api';

const getHeaders = () => {
    const headers: Record<string, string> = {
        'Content-Type': 'application/json',
    };
    const token = localStorage.getItem('token');
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    return headers;
};

export const api = {
    async get(endpoint: string, params: Record<string, any> = {}) {
        const url = new URL(`${API_BASE}${endpoint}`);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

        const response = await fetch(url.toString(), {
            headers: getHeaders()
        });

        if (response.status === 401 || response.status === 403) {
            throw new Error('Unauthorized');
        }

        return response.json();
    },

    async post(endpoint: string, data: any) {
        const headers = getHeaders();
        let body;

        if (data instanceof FormData) {
            delete headers['Content-Type'];
            body = data;
        } else {
            body = JSON.stringify(data);
        }

        const response = await fetch(`${API_BASE}${endpoint}`, {
            method: 'POST',
            headers,
            body,
        });

        if (response.status === 401 || response.status === 403) {
            const errorBody = await response.json().catch(() => ({}));
            throw new Error(errorBody.error || 'Unauthorized');
        }

        return response.json();
    }
};
