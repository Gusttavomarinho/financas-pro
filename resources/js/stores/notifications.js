import { defineStore } from 'pinia';
import axios from 'axios';

export const useNotificationsStore = defineStore('notifications', {
    state: () => ({
        notifications: [],
        unreadCount: 0,
        loading: false,
        error: null,
    }),

    getters: {
        recentNotifications: (state) => state.notifications.slice(0, 5),
        hasUnread: (state) => state.unreadCount > 0,
    },

    actions: {
        async fetchNotifications(limit = 50) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axios.get('/api/notifications', {
                    params: { limit }
                });
                this.notifications = response.data.data;
                this.unreadCount = response.data.unread_count;
            } catch (err) {
                this.error = err.response?.data?.message || 'Erro ao carregar notificações';
                console.error('Fetch notifications error:', err);
            } finally {
                this.loading = false;
            }
        },

        async fetchUnreadOnly() {
            try {
                const response = await axios.get('/api/notifications', {
                    params: { unread: 'true', limit: 10 }
                });
                this.notifications = response.data.data;
                this.unreadCount = response.data.unread_count;
            } catch (err) {
                console.error('Fetch unread error:', err);
            }
        },

        async markAsRead(notificationId) {
            try {
                const response = await axios.post(`/api/notifications/${notificationId}/read`);
                
                // Update local state
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification) {
                    notification.read_at = new Date().toISOString();
                }
                this.unreadCount = response.data.unread_count;
            } catch (err) {
                console.error('Mark as read error:', err);
            }
        },

        async markAllAsRead() {
            try {
                await axios.post('/api/notifications/read-all');
                
                // Update local state
                this.notifications.forEach(n => {
                    if (!n.read_at) {
                        n.read_at = new Date().toISOString();
                    }
                });
                this.unreadCount = 0;
            } catch (err) {
                console.error('Mark all as read error:', err);
            }
        },

        // Get icon for notification type
        getIcon(type) {
            const icons = {
                budget_warning: '⚠️',
                budget_exceeded: '🔴',
                invoice_closed: '📋',
                invoice_due_soon: '⏰',
                recurring_generated: '🔄',
                recurring_failed: '❌',
                import_completed: '✅',
            };
            return icons[type] || '🔔';
        },
    },
});
