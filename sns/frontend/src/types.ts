export interface User {
    id: number;
    username: string;
    display_name: string;
}

export interface Reaction {
    emoji_char: string;
    count: number;
}

export interface Post {
    id: number;
    user_id: number;
    username: string;
    display_name: string;
    type: 'microblog' | 'blog' | 'question' | 'answer';
    content: string;
    title?: string;
    image_path?: string;
    created_at: string;
    quoted_post_id?: number;
    quoted_content?: string;
    quoted_title?: string;
    quoted_image_path?: string;
    quoted_type?: 'microblog' | 'blog' | 'question' | 'answer';
    quoted_username?: string;
    quoted_display_name?: string;
    reactions?: { emoji_char: string, count: number, is_me?: boolean }[];
    reaction_count?: number;
    reply_to_id?: number;
    best_answer_id?: number;
    // Client-side helper or if backend returns it
    status?: 'resolved' | 'open';
}

export interface AuthResponse {
    message?: string;
    error?: string;
    token?: string;
    user?: User;
}
