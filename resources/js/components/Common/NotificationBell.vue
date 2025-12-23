<template>
    <div class="relative" ref="bellContainer">
        <!-- Bell Button -->
        <button
            @click="toggleDropdown"
            class="relative p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <!-- Badge -->
            <span
                v-if="notificationsStore.hasUnread"
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center"
            >
                {{ notificationsStore.unreadCount > 9 ? '9+' : notificationsStore.unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <div
            v-if="showDropdown"
            class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 max-h-96 overflow-hidden"
        >
            <!-- Header -->
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Notificações</h3>
                <button
                    v-if="notificationsStore.hasUnread"
                    @click="markAllAsRead"
                    class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400"
                >
                    Marcar tudo como lido
                </button>
            </div>

            <!-- Loading -->
            <div v-if="notificationsStore.loading" class="p-4 text-center text-gray-500">
                <svg class="animate-spin h-5 w-5 mx-auto" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <!-- Notifications List -->
            <div v-else-if="notificationsStore.recentNotifications.length" class="max-h-72 overflow-y-auto">
                <div
                    v-for="notification in notificationsStore.recentNotifications"
                    :key="notification.id"
                    class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer border-b border-gray-100 dark:border-gray-700/50 last:border-b-0"
                    :class="{ 'bg-blue-50 dark:bg-blue-900/20': !notification.read_at }"
                    @click="handleNotificationClick(notification)"
                >
                    <div class="flex gap-3">
                        <span class="text-xl">{{ notificationsStore.getIcon(notification.type) }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ notification.title }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                {{ notification.message }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                {{ formatTime(notification.created_at) }}
                            </p>
                        </div>
                        <div v-if="!notification.read_at" class="w-2 h-2 bg-blue-500 rounded-full mt-1"></div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="p-8 text-center text-gray-500">
                <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="text-sm">Nenhuma notificação</p>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <RouterLink
                    to="/notifications"
                    class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 block text-center"
                    @click="showDropdown = false"
                >
                    Ver todas as notificações
                </RouterLink>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useNotificationsStore } from '@/stores/notifications';

const notificationsStore = useNotificationsStore();
const showDropdown = ref(false);
const bellContainer = ref(null);

function toggleDropdown() {
    showDropdown.value = !showDropdown.value;
    if (showDropdown.value) {
        notificationsStore.fetchNotifications(10);
    }
}

function handleNotificationClick(notification) {
    if (!notification.read_at) {
        notificationsStore.markAsRead(notification.id);
    }
}

function markAllAsRead() {
    notificationsStore.markAllAsRead();
}

function formatTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    // Less than 1 minute
    if (diff < 60000) return 'Agora';
    
    // Less than 1 hour
    if (diff < 3600000) {
        const mins = Math.floor(diff / 60000);
        return `${mins} min atrás`;
    }
    
    // Less than 1 day
    if (diff < 86400000) {
        const hours = Math.floor(diff / 3600000);
        return `${hours}h atrás`;
    }
    
    // Less than 7 days
    if (diff < 604800000) {
        const days = Math.floor(diff / 86400000);
        return `${days}d atrás`;
    }
    
    // Default: show date
    return date.toLocaleDateString('pt-BR');
}

// Close on click outside
function handleClickOutside(event) {
    if (bellContainer.value && !bellContainer.value.contains(event.target)) {
        showDropdown.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    // Fetch initial unread count
    notificationsStore.fetchNotifications(10);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
