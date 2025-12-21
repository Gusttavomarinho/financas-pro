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
                <p class="text-gray-400 mt-2">Crie sua conta gratuita</p>
            </div>

            <!-- Form -->
            <div class="card">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <div>
                        <label for="name" class="label">Nome completo</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            autofocus
                            :class="['input', errors.name && 'input-error']"
                            placeholder="Seu nome"
                        />
                        <p v-if="errors.name" class="mt-1 text-sm text-danger-500">{{ errors.name[0] }}</p>
                    </div>

                    <div>
                        <label for="email" class="label">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            :class="['input', errors.email && 'input-error']"
                            placeholder="seu@email.com"
                        />
                        <p v-if="errors.email" class="mt-1 text-sm text-danger-500">{{ errors.email[0] }}</p>
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
                        <p v-if="errors.password" class="mt-1 text-sm text-danger-500">{{ errors.password[0] }}</p>
                        <p class="mt-1 text-xs text-gray-500">Mínimo 8 caracteres</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="label">Confirmar senha</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            :class="['input', errors.password_confirmation && 'input-error']"
                            placeholder="••••••••"
                        />
                    </div>

                    <button type="submit" class="btn-primary w-full py-3">
                        Criar conta
                    </button>

                    <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                        Já tem conta?
                        <RouterLink to="/login" class="text-primary-600 hover:text-primary-700 font-medium">
                            Entrar
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
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const errors = ref({});

async function handleSubmit() {
    errors.value = {};
    
    const result = await authStore.register(form);
    
    if (result.success) {
        router.push('/');
    } else if (result.errors) {
        errors.value = result.errors;
    }
}
</script>
