<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notificações</h1>
                <p class="text-gray-500 dark:text-gray-400">Suas notificações e alertas</p>
            </div>
            <button
                v-if="notificationsStore.hasUnread"
                @click="markAllAsRead"
                class="btn-secondary"
            >
                Marcar tudo como lido
            </button>
        </div>

        <!-- Loading -->
        <div v-if="notificationsStore.loading" class="text-center py-12">
            <svg class="animate-spin h-8 w-8 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- Notifications List -->
        <div v-else-if="notificationsStore.notifications.length" class="space-y-3">
            <div
                v-for="notification in notificationsStore.notifications"
                :key="notification.id"
                class="card p-4 cursor-pointer transition-all hover:shadow-md"
                :class="{
                    'border-l-4 border-l-blue-500 bg-blue-50/50 dark:bg-blue-900/20': !notification.read_at,
                    'opacity-75': notification.read_at
                }"
                @click="handleNotificationClick(notification)"
            >
                <div class="flex gap-4">
                    <div class="text-2xl">{{ notificationsStore.getIcon(notification.type) }}</div>
                    <div class="flex-1">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">
                                    {{ notification.title }}
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300 mt-1">
                                    {{ notification.message }}
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDate(notification.created_at) }}
                                </p>
                                <span
                                    v-if="!notification.read_at"
                                    class="inline-block mt-1 px-2 py-0.5 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 rounded-full"
                                >
                                    Nova
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="card p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                Nenhuma notificação
            </h3>
            <p class="text-gray-500 dark:text-gray-400">
                Você será notificado sobre eventos importantes como orçamentos, faturas e recorrências.
            </p>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useNotificationsStore } from '@/stores/notifications';

const notificationsStore = useNotificationsStore();

function handleNotificationClick(notification) {
    if (!notification.read_at) {
        notificationsStore.markAsRead(notification.id);
    }
}

function markAllAsRead() {
    notificationsStore.markAllAsRead();
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    // Less than 1 minute
    if (diff < 60000) return 'Agora mesmo';
    
    // Less than 1 hour
    if (diff < 3600000) {
        const mins = Math.floor(diff / 60000);
        return `${mins} minuto${mins > 1 ? 's' : ''} atrás`;
    }
    
    // Less than 1 day
    if (diff < 86400000) {
        const hours = Math.floor(diff / 3600000);
        return `${hours} hora${hours > 1 ? 's' : ''} atrás`;
    }
    
    // Less than 7 days
    if (diff < 604800000) {
        const days = Math.floor(diff / 86400000);
        return `${days} dia${days > 1 ? 's' : ''} atrás`;
    }
    
    // Default
    return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

onMounted(() => {
    notificationsStore.fetchNotifications();
});
</script>
