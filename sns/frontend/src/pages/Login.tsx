import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { api } from '../api';

interface LoginProps {
    onLogin: (token: string, user: any) => void;
}

export const Login: React.FC<LoginProps> = ({ onLogin }) => {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const navigate = useNavigate();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');
        try {
            const res = await api.post('/login', { username, password });
            if (res.token && res.user) {
                onLogin(res.token, res.user);
                navigate('/');
            } else {
                setError('ログインに失敗しました。');
            }
        } catch (err: any) {
            console.error(err);
            setError(err.message || 'ログインエラー');
        }
    };

    return (
        <div style={{ maxWidth: '400px', margin: '50px auto', padding: '20px', border: '1px solid #ddd', borderRadius: '8px' }}>
            <h2>ログイン</h2>
            {error && <p style={{ color: 'red' }}>{error}</p>}
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
                <button type="submit" style={{ padding: '10px' }}>ログイン</button>
            </form>
            <p style={{ marginTop: '15px' }}>
                アカウントをお持ちでないですか？ <Link to="/register">登録はこちら</Link>
            </p>
        </div>
    );
};
