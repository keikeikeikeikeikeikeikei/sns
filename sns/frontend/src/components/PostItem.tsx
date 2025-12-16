import React, { useState } from 'react';
import EmojiPicker, { EmojiClickData } from 'emoji-picker-react';
import { Post, Reaction } from '../types';
import { api } from '../api';

interface PostItemProps {
    post: Post;
    currentUserId?: number;
    onQuote: (post: Post) => void;
}

export const PostItem: React.FC<PostItemProps> = ({ post, currentUserId, onQuote }) => {
    const [reactions, setReactions] = useState<{ emoji_char: string, count: number, is_me?: boolean }[]>((post.reactions as unknown as { emoji_char: string, count: number, is_me?: boolean }[]) || []);
    const [showPicker, setShowPicker] = useState(false);

    const handleReaction = async (emoji: string) => {
        if (!currentUserId) return alert('まずはログインしてください');

        try {
            const res = await api.post('/reactions', { post_id: post.id, emoji });

            // Local update based on message
            setReactions(prev => {
                const existingIndex = prev.findIndex(r => r.emoji_char === emoji);
                if (res.message === 'Reaction added.') {
                    if (existingIndex >= 0) {
                        const newReactions = [...prev];
                        newReactions[existingIndex].count++;
                        newReactions[existingIndex].is_me = true;
                        return newReactions;
                    } else {
                        return [...prev, { emoji_char: emoji, count: 1, is_me: true }];
                    }
                } else if (res.message === 'Reaction removed.') {
                    if (existingIndex >= 0) {
                        const newReactions = [...prev];
                        newReactions[existingIndex].count--;
                        newReactions[existingIndex].is_me = false;
                        if (newReactions[existingIndex].count <= 0) {
                            return newReactions.filter(r => r.emoji_char !== emoji);
                        }
                        return newReactions;
                    }
                }
                return prev;
            });

        } catch (e) {
            console.error(e);
            alert('リアクションに失敗しました');
        }
    };

    const onEmojiClick = async (emojiData: EmojiClickData) => {
        await handleReaction(emojiData.emoji);
        setShowPicker(false);
    };

    const togglePicker = () => {
        if (!currentUserId) {
            alert('まずはログインしてください');
            return;
        }
        setShowPicker(!showPicker);
    };

    return (
        <div className="post-item" style={{ border: '1px solid #ddd', padding: '15px', marginBottom: '10px', borderRadius: '8px', background: '#fff' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', color: '#666', fontSize: '0.9em' }}>
                <span>{post.display_name} (@{post.username})</span>
                <span>{new Date(post.created_at).toLocaleString('ja-JP')}</span>
            </div>

            {post.title && <h3>{post.title}</h3>}
            <p style={{ whiteSpace: 'pre-wrap' }}>{post.content}</p>

            {post.image_path && (
                <div style={{ marginTop: '10px', marginBottom: '10px' }}>
                    <img
                        src={`http://localhost:8000${post.image_path}`}
                        alt="添付画像"
                        style={{ maxWidth: '100%', maxHeight: '400px', borderRadius: '8px', objectFit: 'contain' }}
                    />
                </div>
            )}

            {post.quoted_post_id && (
                <div className="quoted-post" style={{ border: '1px solid #ccc', padding: '10px', margin: '10px 0', borderRadius: '8px', background: '#f9f9f9' }}>
                    <small>引用: @{post.quoted_username}</small>
                    {post.quoted_title && <h4>{post.quoted_title}</h4>}
                    <p>{post.quoted_content}</p>
                    {post.quoted_image_path && (
                        <div style={{ marginTop: '5px' }}>
                            <img
                                src={`http://localhost:8000${post.quoted_image_path}`}
                                alt="引用画像"
                                style={{ maxWidth: '100%', maxHeight: '200px', borderRadius: '4px', objectFit: 'contain' }}
                            />
                        </div>
                    )}
                </div>
            )}

            <div className="actions" style={{ marginTop: '10px', display: 'flex', gap: '10px', alignItems: 'center', position: 'relative' }}>
                <button onClick={togglePicker}>
                    リアクション
                </button>

                <div style={{ display: 'flex', gap: '5px' }}>
                    {reactions.map((r) => (
                        <span
                            key={r.emoji_char}
                            onClick={() => handleReaction(r.emoji_char)}
                            style={{
                                background: r.is_me ? '#e1f5fe' : '#eee',
                                border: r.is_me ? '1px solid #2196f3' : '1px solid transparent',
                                padding: '2px 5px',
                                borderRadius: '4px',
                                cursor: 'pointer',
                                userSelect: 'none'
                            }}
                        >
                            {r.emoji_char} {r.count}
                        </span>
                    ))}
                </div>

                <button onClick={() => onQuote(post)}>引用</button>

                {showPicker && (
                    <div style={{ position: 'absolute', top: '100%', left: '0', zIndex: 10 }}>
                        <EmojiPicker onEmojiClick={onEmojiClick} />
                    </div>
                )}
            </div>
        </div>
    );
};
