import React, { useState } from 'react';
import { Feed } from '../components/Feed';
import { api } from '../api';
import { Post, User } from '../types';

interface HomeProps {
    user: User | null;
    onLogout: () => void;
}

export const Home: React.FC<HomeProps> = ({ user, onLogout }) => {
    const [activeTab, setActiveTab] = useState<'microblog' | 'blog'>('microblog');
    const [searchQuery, setSearchQuery] = useState('');

    const [postContent, setPostContent] = useState('');
    const [postTitle, setPostTitle] = useState('');
    const [postImage, setPostImage] = useState<File | null>(null);
    const [quotedPost, setQuotedPost] = useState<Post | null>(null);
    const [refreshTrigger, setRefreshTrigger] = useState(0);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            setPostImage(e.target.files[0]);
        }
    };

    const handlePost = async () => {
        try {
            const formData = new FormData();
            formData.append('type', activeTab);
            formData.append('content', postContent);
            if (activeTab === 'blog' && postTitle) {
                formData.append('title', postTitle);
            }
            if (quotedPost) {
                formData.append('quoted_post_id', String(quotedPost.id));
            }
            if (postImage) {
                formData.append('image', postImage);
            }

            await api.post('/posts', formData);

            setPostContent('');
            setPostTitle('');
            setPostImage(null);
            setQuotedPost(null);
            setRefreshTrigger(prev => prev + 1);
        } catch (e) {
            console.error(e);
            alert('投稿に失敗しました');
        }
    };

    return (
        <div className="container">
            <header style={{ marginBottom: '20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderBottom: '1px solid #eee', paddingBottom: '10px' }}>
                <div style={{ display: 'flex', gap: '20px', alignItems: 'center' }}>
                    <h1 style={{ margin: 0, fontSize: '1.5em' }}>SNS_2B</h1>
                    <nav style={{ display: 'flex', gap: '15px' }}>
                        <span style={{ fontWeight: 'bold' }}>ホーム</span>
                        <a href="/qa" style={{ textDecoration: 'none', color: '#007bff', fontWeight: 'bold' }}>Q&A</a>
                    </nav>
                </div>
                <div>
                    ログイン中: <strong>{user?.display_name || 'ユーザー'}</strong>
                    <button onClick={onLogout} style={{ marginLeft: '10px' }}>ログアウト</button>
                </div>
            </header>

            <div style={{ marginBottom: '20px' }}>
                <input
                    placeholder="投稿を検索..."
                    value={searchQuery}
                    onChange={e => setSearchQuery(e.target.value)}
                    style={{ width: '100%', padding: '10px', fontSize: '16px' }}
                />
            </div>

            <div className="nav-tabs">
                <div className={`nav-tab ${activeTab === 'microblog' ? 'active' : ''}`} onClick={() => setActiveTab('microblog')}>
                    つぶやき
                </div>
                <div className={`nav-tab ${activeTab === 'blog' ? 'active' : ''}`} onClick={() => setActiveTab('blog')}>
                    ブログ
                </div>
            </div>

            <div className="post-card" style={{ marginBottom: '20px', padding: '15px', background: '#fff', borderRadius: '10px' }}>
                <h3>投稿を作成 ({activeTab === 'microblog' ? 'つぶやき' : 'ブログ'})</h3>
                {quotedPost && (
                    <div className="quote-box">
                        <small>引用: @{quotedPost.username}</small>
                        <div>{quotedPost.content}</div>
                        <button onClick={() => setQuotedPost(null)}>引用をキャンセル</button>
                    </div>
                )}

                {activeTab === 'blog' && (
                    <input
                        placeholder="タイトル"
                        value={postTitle}
                        onChange={e => setPostTitle(e.target.value)}
                        style={{ display: 'block', width: '100%', marginBottom: '10px', padding: '8px' }}
                    />
                )}

                <textarea
                    placeholder={`今何してる？ (${activeTab === 'microblog' ? '最大150文字' : '制限なし'})`}
                    value={postContent}
                    onChange={e => setPostContent(e.target.value)}
                    style={{ width: '100%', height: '80px', padding: '8px' }}
                />

                <div style={{ marginTop: '10px' }}>
                    <input type="file" accept="image/*" onChange={handleFileChange} />
                </div>

                <button onClick={handlePost} style={{ marginTop: '10px' }}>投稿 (画像付き)</button>
            </div>

            <Feed
                type={activeTab}
                currentUserId={user?.id}
                onQuote={(post) => setQuotedPost(post)}
                refreshTrigger={refreshTrigger}
                searchQuery={searchQuery}
            />
        </div>
    );
};
