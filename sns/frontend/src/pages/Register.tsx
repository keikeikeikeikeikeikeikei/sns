import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { api } from '../api';

export const Register: React.FC = () => {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const navigate = useNavigate();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            const res = await api.post('/register', { username, password });
            alert(res.message || '登録しました！ログインしてください。');
            navigate('/login');
        } catch (err) {
            console.error(err);
            alert('登録エラー');
        }
    };

    return (
        <div style={{ maxWidth: '400px', margin: '50px auto', padding: '20px', border: '1px solid #ddd', borderRadius: '8px' }}>
            <h2>登録</h2>
            <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                <input
                    placeholder="ユーザー名"
                    value={username}
                    onChange={e => setUsername(e.target.value)}
                    style={{ padding: '10px' }}
                />
                <input
                    type="password"
                    placeholder="パスワード"
                    value={password}
                    onChange={e => setPassword(e.target.value)}
                    style={{ padding: '10px' }}
                />
                <button type="submit" style={{ padding: '10px' }}>登録</button>
            </form>
            <p style={{ marginTop: '15px' }}>
                すでにアカウントをお持ちですか？ <Link to="/login">ログインはこちら</Link>
            </p>
        </div>
    );
};
