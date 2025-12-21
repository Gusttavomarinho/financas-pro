<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 py-12 px-4">
        <div class="max-w-md w-full">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 mb-4">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white">FinançasPro</h1>
                <p class="text-gray-400 mt-2">Entre na sua conta</p>
            </div>

            <!-- Form -->
            <div class="card">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <div>
                        <label for="email" class="label">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            :class="['input', errors.email && 'input-error']"
                            placeholder="seu@email.com"
                        />
                        <p v-if="errors.email" class="mt-1 text-sm text-danger-500">{{ errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="label">Senha</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            :class="['input', errors.password && 'input-error']"
                            placeholder="••••••••"
                        />
                        <p v-if="errors.password" class="mt-1 text-sm text-danger-500">{{ errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">Lembrar de mim</span>
                        </label>

                        <a href="#" class="text-sm text-primary-600 hover:text-primary-700">
                            Esqueci a senha
                        </a>
                    </div>

                    <button type="submit" class="btn-primary w-full py-3">
                        Entrar
                    </button>

                    <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                        Não tem conta?
                        <RouterLink to="/register" class="text-primary-600 hover:text-primary-700 font-medium">
                            Criar conta
                        </RouterLink>
                    </p>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
    email: '',
    password: '',
    remember: false,
});

const errors = ref({});

async function handleSubmit() {
    errors.value = {};
    
    const result = await authStore.login({
        email: form.email,
        password: form.password,
    });
    
    if (result.success) {
        router.push('/');
    } else if (result.errors) {
        errors.value = result.errors;
    }
}
</script>
