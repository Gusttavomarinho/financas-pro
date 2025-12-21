import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useUiStore = defineStore('ui', () => {
    const isLoading = ref(false);
    const toasts = ref([]);
    const sidebarOpen = ref(true);
    const darkMode = ref(localStorage.getItem('darkMode') === 'true');

    // Apply dark mode on init
    if (darkMode.value) {
        document.documentElement.classList.add('dark');
    }

    function setLoading(value) {
        isLoading.value = value;
    }

    function showToast(message, type = 'info', duration = 5000) {
        const id = Date.now();
        toasts.value.push({ id, message, type });

        if (duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, duration);
        }
    }

    function removeToast(id) {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    }

    function toggleSidebar() {
        sidebarOpen.value = !sidebarOpen.value;
    }

    function toggleDarkMode() {
        darkMode.value = !darkMode.value;
        localStorage.setItem('darkMode', darkMode.value);

        if (darkMode.value) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

    return {
        isLoading,
        toasts,
        sidebarOpen,
        darkMode,
        setLoading,
        showToast,
        removeToast,
        toggleSidebar,
        toggleDarkMode,
    };
});
