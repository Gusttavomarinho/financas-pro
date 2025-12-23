<template>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-40 w-64 bg-gray-900 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto',
                uiStore.sidebarOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="flex items-center gap-3 h-16 px-6 border-b border-gray-800">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-white">FinançasPro</span>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <RouterLink
                        v-for="item in navigation"
                        :key="item.name"
                        :to="item.to"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive(item.to)
                                ? 'bg-primary-600 text-white'
                                : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                        ]"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        {{ item.name }}
                    </RouterLink>
                </nav>

                <!-- User section -->
                <div class="p-4 border-t border-gray-800">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center">
                            <span class="text-white font-medium">{{ userInitials }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ authStore.user?.name }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ authStore.user?.email }}</p>
                        </div>
                    </div>
                    <button
                        @click="handleLogout"
                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Sair
                    </button>
                </div>
            </div>
        </aside>

        <!-- Overlay -->
        <div
            v-if="uiStore.sidebarOpen"
            @click="uiStore.toggleSidebar"
            class="fixed inset-0 bg-black/50 z-30 lg:hidden"
        />

        <!-- Main content -->
        <div class="flex-1 flex flex-col min-h-screen lg:ml-0">
            <!-- Top bar -->
            <header class="sticky top-0 z-20 h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center px-4 lg:px-6">
                <button
                    @click="uiStore.toggleSidebar"
                    class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="flex-1" />

                <!-- Quick actions -->
                <div class="flex items-center gap-2">
                    <RouterLink
                        to="/transactions/create"
                        class="btn-primary"
                    >
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="hidden sm:inline">Novo Lançamento</span>
                    </RouterLink>

                    <!-- Notifications Bell -->
                    <NotificationBell />

                    <button
                        @click="uiStore.toggleDarkMode"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        <svg v-if="uiStore.darkMode" class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg v-else class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 p-4 lg:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, h } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import NotificationBell from '@/components/Common/NotificationBell.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const uiStore = useUiStore();

const userInitials = computed(() => {
    const name = authStore.user?.name || '';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

// Simple icon components
const DashboardIcon = {
    render() {
        return h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' })
        ]);
    }
};

const AccountsIcon = {
    render() {
        return h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' })
        ]);
    }
};

const CardsIcon = {
    render() {
        return h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' })
        ]);
    }
};

const TransactionsIcon = {
    render() {
        return h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' })
        ]);
    }
};

const CategoriesIcon = {
    render() {
        return h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z' })
        ]);
    }
};

const SettingsIcon = {
    render() {
        return h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' }),
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z' })
        ]);
    }
};

const RecurrencesIcon = {
    render() {
        return h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' })
        ]);
    }
};

const BudgetsIcon = {
    render() {
        return h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' })
        ]);
    }
};

const GoalsIcon = {
    render() {
        return h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z' })
        ]);
    }
};

const ChartsIcon = {
    render() {
        return h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z' }),
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z' })
        ]);
    }
};

const CalendarIcon = {
    render() {
        return h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' })
        ]);
    }
};

const ImportIcon = {
    render() {
        return h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12' })
        ]);
    }
};

const navigation = [
    { name: 'Dashboard', to: '/', icon: DashboardIcon },
    { name: 'Contas', to: '/accounts', icon: AccountsIcon },
    { name: 'Cartões', to: '/cards', icon: CardsIcon },
    { name: 'Lançamentos', to: '/transactions', icon: TransactionsIcon },
    { name: 'Importar', to: '/import', icon: ImportIcon },
    { name: 'Recorrências', to: '/recurrences', icon: RecurrencesIcon },
    { name: 'Orçamentos', to: '/budgets', icon: BudgetsIcon },
    { name: 'Objetivos', to: '/goals', icon: GoalsIcon },
    { name: 'Calendário', to: '/calendar', icon: CalendarIcon },
    { name: 'Gráficos', to: '/charts', icon: ChartsIcon },
    { name: 'Categorias', to: '/categories', icon: CategoriesIcon },
    { name: 'Configurações', to: '/settings', icon: SettingsIcon },
];

function isActive(path) {
    if (path === '/') {
        return route.path === '/';
    }
    return route.path.startsWith(path);
}

async function handleLogout() {
    await authStore.logout();
    router.push('/login');
}
</script>
