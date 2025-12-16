import React, { useState, useEffect } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { Login } from './pages/Login';
import { Register } from './pages/Register';
import { Home } from './pages/Home';
import { QAList } from './pages/QAList';
import { QADetail } from './pages/QADetail';
import { api } from './api';
import { User } from './types';
import './index.css';

// Helper for protected routes
const ProtectedRoute = ({ children }: { children: JSX.Element }) => {
    const token = localStorage.getItem('token');
    if (!token) {
        return <Navigate to="/login" replace />;
    }
    return children;
};

function App() {
    const [token, setToken] = useState<string | null>(localStorage.getItem('token'));
    const [user, setUser] = useState<User | null>(null);

    useEffect(() => {
        const storedUser = localStorage.getItem('user');
        if (storedUser && token) {
            setUser(JSON.parse(storedUser));
        }
    }, [token]);

    const handleLogin = (newToken: string, newUser: User) => {
        localStorage.setItem('token', newToken);
        localStorage.setItem('user', JSON.stringify(newUser));
        setToken(newToken);
        setUser(newUser);
    };

    const handleLogout = () => {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        setToken(null);
        setUser(null);
    };

    return (
        <BrowserRouter>
            <Routes>
                <Route
                    path="/login"
                    element={!token ? <Login onLogin={handleLogin} /> : <Navigate to="/home" />}
                />
                <Route
                    path="/register"
                    element={!token ? <Register /> : <Navigate to="/home" />}
                />
                <Route path="/home" element={
                    <ProtectedRoute>
                        <Home user={user} onLogout={handleLogout} />
                    </ProtectedRoute>
                } />
                <Route path="/qa" element={
                    <ProtectedRoute>
                        <QAList />
                    </ProtectedRoute>
                } />
                <Route path="/qa/:id" element={
                    <ProtectedRoute>
                        <QADetail />
                    </ProtectedRoute>
                } />
                <Route path="/" element={<Navigate to="/home" />} />
                {/* Fallback */}
                <Route path="*" element={<Navigate to="/home" />} />
            </Routes>
        </BrowserRouter>
    );
}

export default App;
