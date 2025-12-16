import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { api } from '../api';
import { Post } from '../types';

export const QADetail: React.FC = () => {
    const { id } = useParams<{ id: string }>();
    const [question, setQuestion] = useState<Post | null>(null);
    const [answers, setAnswers] = useState<Post[]>([]);
    const [newAnswer, setNewAnswer] = useState('');
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();

    // Check if current user is owner (needs auth state from App or context, but for MVP check local storage or assume)
    const [currentUser, setCurrentUser] = useState<any>(null); // Simplified

    useEffect(() => {
        const fetchUser = async () => {
            // In a real app, use Context. Here verify token?
            // Actually, we can decode token or fetch /me.
            // We don't have /me. Let's rely on stored user info if any, or just check if "Select Best Answer" works (backend verifies).
            // But to show the button we need to know.
            // Let's assume user id is stored in localStorage 'user' for now?
            // Login.tsx doesn't store user in localStorage, only token.
            // Let's skip pure frontend ownership check for UI for now, or fetch who am I.
            // Let's just render the button if logged in and let backend reject? 
            // Better: decode token.
        };
        fetchUser();
    }, []);

    const fetchData = async () => {
        if (!id) return;
        try {
            // Fetch Question
            const qRes = await api.get('/posts', { id: id, type: 'question' }); // Ensure we find it even if filter strict
            // If API returns array
            if (Array.isArray(qRes) && qRes.length > 0) {
                setQuestion(qRes[0]);
            } else {
                setQuestion(null);
            }

            // Fetch Answers
            const aRes = await api.get('/posts', { reply_to_id: id });
            setAnswers(aRes);

        } catch (e) {
            console.error(e);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [id]);

    const submitAnswer = async () => {
        if (!newAnswer.trim()) return;
        try {
            await api.post('/posts', {
                type: 'answer',
                content: newAnswer,
                title: 'Answer', // Required by validator?
                reply_to_id: id ? parseInt(id) : null
            });
            setNewAnswer('');
            fetchData(); // Reload
        } catch (e) {
            alert('回答の投稿に失敗しました');
        }
    };

    const handleBestAnswer = async (answerId: number) => {
        if (!id) return;
        if (!confirm('この回答をベストアンサーにしますか？')) return;
        try {
            await api.post('/posts/best_answer', {
                question_id: parseInt(id),
                answer_id: answerId
            });
            fetchData();
        } catch (e) {
            alert('ベストアンサーの選択に失敗しました（権限がない可能性があります）');
        }
    };

    if (loading) return <div>読み込み中...</div>;
    if (!question) return <div>質問が見つかりません。</div>;

    return (
        <div style={{ maxWidth: '800px', margin: '0 auto', padding: '20px' }}>
            <button onClick={() => navigate('/qa')} style={{ marginBottom: '10px' }}>&lt; 一覧に戻る</button>

            <div className="question-box" style={{ border: '2px solid #333', padding: '20px', borderRadius: '8px', background: '#fff', marginBottom: '30px' }}>
                <h1 style={{ marginTop: 0 }}>{question.title}</h1>
                <p style={{ whiteSpace: 'pre-wrap', fontSize: '1.1em' }}>{question.content}</p>
                <div style={{ color: '#666', marginTop: '10px' }}>
                    Asked by {question.display_name} | {new Date(question.created_at).toLocaleString()}
                </div>
                {question.best_answer_id && <div style={{ marginTop: '10px', color: 'green', fontWeight: 'bold' }}>✓ 解決済み</div>}
            </div>

            <h3>回答 ({answers.length})</h3>

            <div className="answers-list" style={{ marginBottom: '30px' }}>
                {answers.map(a => {
                    const isBest = question.best_answer_id === a.id;
                    return (
                        <div key={a.id} style={{
                            border: isBest ? '2px solid gold' : '1px solid #ddd',
                            padding: '15px',
                            marginBottom: '10px',
                            borderRadius: '8px',
                            background: isBest ? '#fffbf0' : '#fff'
                        }}>
                            {isBest && <div style={{ color: 'gold', fontWeight: 'bold', marginBottom: '5px' }}>★ ベストアンサー</div>}
                            <p style={{ whiteSpace: 'pre-wrap' }}>{a.content}</p>
                            <div style={{ fontSize: '0.9em', color: '#666' }}>
                                Answered by {a.display_name} | {new Date(a.created_at).toLocaleString()}
                            </div>

                            {/* Show "Set Best Answer" only if question is open (or can change?) and I am owner? 
                                 Backend checks ownership. UI can show it always but it will fail if not owner. 
                                 For better UX, we should check user. But for now, let's keep it simple. */}
                            <div style={{ marginTop: '10px' }}>
                                <button onClick={() => handleBestAnswer(a.id)} disabled={!!question.best_answer_id && question.best_answer_id !== a.id}>
                                    {isBest ? 'ベストアンサー解除' : 'ベストアンサーにする'}
                                </button>
                            </div>
                        </div>
                    );
                })}
                {answers.length === 0 && <p>まだ回答はありません。</p>}
            </div>

            <div className="answer-form" style={{ borderTop: '1px solid #eee', paddingTop: '20px' }}>
                <h4>回答する</h4>
                <textarea
                    value={newAnswer}
                    onChange={e => setNewAnswer(e.target.value)}
                    style={{ width: '100%', minHeight: '100px', padding: '10px', marginBottom: '10px' }}
                    placeholder="回答を入力..."
                />
                <button onClick={submitAnswer} style={{ padding: '10px 20px', background: '#007bff', color: '#fff', border: 'none', borderRadius: '4px', cursor: 'pointer' }}>
                    回答を投稿
                </button>
            </div>
        </div>
    );
};
