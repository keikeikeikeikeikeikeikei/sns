import React, { useEffect, useState } from 'react';
import { api } from '../api';
import { Post } from '../types';
import { useNavigate } from 'react-router-dom';

export const QAList: React.FC = () => {
    const [questions, setQuestions] = useState<Post[]>([]);
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();

    useEffect(() => {
        const fetchQuestions = async () => {
            try {
                const data = await api.get('/posts', { type: 'question' });
                setQuestions(data);
            } catch (e) {
                console.error(e);
            } finally {
                setLoading(false);
            }
        };
        fetchQuestions();
    }, []);

    if (loading) return <div>読み込み中...</div>;

    return (
        <div style={{ maxWidth: '800px', margin: '0 auto', padding: '20px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <h2>Q&A - 質問一覧</h2>
                <button onClick={() => navigate('/home')} style={{ padding: '5px 10px' }}>ホームへ戻る</button>
            </div>

            <div className="qa-list">
                {questions.map(q => (
                    <div
                        key={q.id}
                        onClick={() => navigate(`/qa/${q.id}`)}
                        style={{
                            border: '1px solid #ddd',
                            padding: '15px',
                            marginBottom: '10px',
                            borderRadius: '8px',
                            background: '#fff',
                            cursor: 'pointer',
                            transition: 'background 0.2s'
                        }}
                        onMouseOver={(e) => e.currentTarget.style.background = '#f9f9f9'}
                        onMouseOut={(e) => e.currentTarget.style.background = '#fff'}
                    >
                        <div style={{ fontWeight: 'bold', fontSize: '1.2em', marginBottom: '5px' }}>
                            {q.status === 'resolved' && <span style={{ color: 'green', marginRight: '5px' }}>[解決済]</span>}
                            {q.title || '無題の質問'}
                        </div>
                        <div style={{ color: '#666', fontSize: '0.9em' }}>
                            Posted by {q.display_name} | {new Date(q.created_at).toLocaleString()} | 回答数: ?
                        </div>
                    </div>
                ))}
                {questions.length === 0 && <p>質問はまだありません。</p>}
            </div>
        </div>
    );
};
