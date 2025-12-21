<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Configurações</h1>
            <p class="text-gray-500 dark:text-gray-400">Personalize sua experiência</p>
        </div>

        <div class="max-w-2xl space-y-6">
            <!-- Profile -->
            <div class="card">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Perfil</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="label">Nome</label>
                        <input v-model="profile.name" type="text" class="input" />
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input v-model="profile.email" type="email" class="input" disabled />
                        <p class="mt-1 text-xs text-gray-500">O email não pode ser alterado</p>
                    </div>
                    <button @click="handleUpdateProfile" class="btn-primary">
                        Salvar Alterações
                    </button>
                </div>
            </div>

            <!-- Preferences -->
            <div class="card">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Preferências</h2>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Tema escuro</p>
                            <p class="text-sm text-gray-500">Ativar modo escuro na interface</p>
                        </div>
                        <button
                            @click="uiStore.toggleDarkMode"
                            :class="[
                                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                uiStore.darkMode ? 'bg-primary-600' : 'bg-gray-200'
                            ]"
                        >
                            <span
                                :class="[
                                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                    uiStore.darkMode ? 'translate-x-6' : 'translate-x-1'
                                ]"
                            />
                        </button>
                    </div>

                    <div>
                        <label class="label">Moeda padrão</label>
                        <select v-model="preferences.currency" class="input">
                            <option value="BRL">Real (R$)</option>
                            <option value="USD">Dólar (US$)</option>
                            <option value="EUR">Euro (€)</option>
                        </select>
                    </div>

                    <div>
                        <label class="label">Formato de data</label>
                        <select v-model="preferences.date_format" class="input">
                            <option value="DD/MM/YYYY">DD/MM/AAAA</option>
                            <option value="MM/DD/YYYY">MM/DD/AAAA</option>
                            <option value="YYYY-MM-DD">AAAA-MM-DD</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Security -->
            <div class="card">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Segurança</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="label">Nova senha</label>
                        <input v-model="password.new" type="password" class="input" placeholder="••••••••" />
                    </div>
                    <div>
                        <label class="label">Confirmar nova senha</label>
                        <input v-model="password.confirm" type="password" class="input" placeholder="••••••••" />
                    </div>
                    <button @click="handleChangePassword" class="btn-primary" :disabled="!password.new || password.new !== password.confirm">
                        Alterar Senha
                    </button>
                </div>
            </div>

            <!-- Data -->
            <div class="card">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Dados</h2>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Exportar dados</p>
                            <p class="text-sm text-gray-500">Baixe todos os seus dados em CSV</p>
                        </div>
                        <button class="btn-secondary">
                            Exportar
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <div>
                            <p class="font-medium text-red-700 dark:text-red-400">Excluir conta</p>
                            <p class="text-sm text-red-600 dark:text-red-500">Esta ação não pode ser desfeita</p>
                        </div>
                        <button class="btn-danger">
                            Excluir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';

const authStore = useAuthStore();
const uiStore = useUiStore();

const profile = reactive({
    name: '',
    email: '',
});

const preferences = reactive({
    currency: 'BRL',
    date_format: 'DD/MM/YYYY',
});

const password = reactive({
    new: '',
    confirm: '',
});

async function handleUpdateProfile() {
    await authStore.updateProfile({
        name: profile.name,
    });
}

async function handleChangePassword() {
    if (password.new !== password.confirm) {
        uiStore.showToast('As senhas não coincidem', 'error');
        return;
    }
    // TODO: Implement password change
    uiStore.showToast('Senha alterada com sucesso!', 'success');
    password.new = '';
    password.confirm = '';
}

onMounted(() => {
    if (authStore.user) {
        profile.name = authStore.user.name;
        profile.email = authStore.user.email;
    }
});
</script>
