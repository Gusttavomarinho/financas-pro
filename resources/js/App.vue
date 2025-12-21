<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Loading overlay -->
        <Transition name="fade">
            <div v-if="uiStore.isLoading" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 flex items-center gap-4 shadow-xl">
                    <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-200 font-medium">Carregando...</span>
                </div>
            </div>
        </Transition>

        <!-- Toast notifications -->
        <div class="fixed top-4 right-4 z-50 space-y-2">
            <TransitionGroup name="slide">
                <div
                    v-for="toast in uiStore.toasts"
                    :key="toast.id"
                    :class="[
                        'px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 min-w-[300px]',
                        toast.type === 'success' ? 'bg-green-600 text-white' : '',
                        toast.type === 'error' ? 'bg-red-600 text-white' : '',
                        toast.type === 'warning' ? 'bg-yellow-500 text-white' : '',
                        toast.type === 'info' ? 'bg-blue-600 text-white' : '',
                    ]"
                >
                    <span class="flex-1">{{ toast.message }}</span>
                    <button @click="uiStore.removeToast(toast.id)" class="hover:opacity-75">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>

        <!-- Main layout -->
        <template v-if="authStore.isAuthenticated">
            <AppLayout>
                <RouterView />
            </AppLayout>
        </template>
        <template v-else>
            <RouterView />
        </template>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterView } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import AppLayout from '@/components/Layout/AppLayout.vue';

const authStore = useAuthStore();
const uiStore = useUiStore();

onMounted(async () => {
    // Check if user is authenticated
    await authStore.checkAuth();
});
</script>

<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
    transition: all 0.3s ease;
}

.slide-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.slide-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>
