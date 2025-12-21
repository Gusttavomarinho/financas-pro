import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import { useUiStore } from './ui';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const token = ref(localStorage.getItem('token') || null);
    const authChecked = ref(false);

    const isAuthenticated = computed(() => !!user.value);

    // Configure axios defaults
    if (token.value) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
    }

    async function checkAuth() {
        if (authChecked.value) return;

        if (token.value) {
            try {
                const response = await axios.get('/api/auth/user');
                user.value = response.data.data;
            } catch (error) {
                // Token invalid or expired
                logout();
            }
        }
        authChecked.value = true;
    }

    async function login(credentials) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.post('/api/auth/login', credentials);
            const { user: userData, token: newToken } = response.data.data;

            user.value = userData;
            token.value = newToken;

            localStorage.setItem('token', newToken);
            axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;

            uiStore.showToast('Login realizado com sucesso!', 'success');
            return { success: true };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao fazer login';
            uiStore.showToast(message, 'error');
            return { success: false, message };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function register(data) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.post('/api/auth/register', data);
            const { user: userData, token: newToken } = response.data.data;

            user.value = userData;
            token.value = newToken;

            localStorage.setItem('token', newToken);
            axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;

            uiStore.showToast('Conta criada com sucesso!', 'success');
            return { success: true };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao criar conta';
            const errors = error.response?.data?.errors || {};
            uiStore.showToast(message, 'error');
            return { success: false, message, errors };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function logout() {
        const uiStore = useUiStore();

        try {
            if (token.value) {
                await axios.post('/api/auth/logout');
            }
        } catch (error) {
            // Ignore logout errors
        }

        user.value = null;
        token.value = null;
        localStorage.removeItem('token');
        delete axios.defaults.headers.common['Authorization'];

        uiStore.showToast('Logout realizado', 'info');
    }

    async function updateProfile(data) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.put('/api/auth/profile', data);
            user.value = response.data.data;
            uiStore.showToast('Perfil atualizado com sucesso!', 'success');
            return { success: true };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao atualizar perfil';
            uiStore.showToast(message, 'error');
            return { success: false, message };
        } finally {
            uiStore.setLoading(false);
        }
    }

    return {
        user,
        token,
        authChecked,
        isAuthenticated,
        checkAuth,
        login,
        register,
        logout,
        updateProfile,
    };
});
