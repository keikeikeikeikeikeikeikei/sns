import React, { useEffect, useState } from 'react';
import { api } from '../api';
import { PostItem } from './PostItem';
import { Post } from '../types';

interface FeedProps {
    type: string;
    currentUserId?: number;
    onQuote: (post: Post) => void;
    refreshTrigger: number;
    searchQuery?: string;
}

export const Feed: React.FC<FeedProps> = ({ type, currentUserId, onQuote, refreshTrigger, searchQuery }) => {
    const [posts, setPosts] = useState<Post[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const fetchPosts = async () => {
            setLoading(true);
            try {
                // If searchQuery exists, we might want to ignore 'type' or filters
                // The backend handles q + type logic (AND condition)
                const params: any = { type };
                if (searchQuery) {
                    params.q = searchQuery;
                }
                const data = await api.get('/posts', params);
                setPosts(data);
            } catch (e) {
                console.error(e);
            } finally {
                setLoading(false);
            }
        };
        fetchPosts();
    }, [type, refreshTrigger, searchQuery]);

    if (loading) return <div>読み込み中...</div>;

    return (
        <div>
            {posts.map(post => (
                <PostItem
                    key={post.id}
                    post={post}
                    currentUserId={currentUserId}
                    onQuote={onQuote}
                />
            ))}
            {posts.length === 0 && <div>投稿が見つかりません。</div>}
        </div>
    );
};
