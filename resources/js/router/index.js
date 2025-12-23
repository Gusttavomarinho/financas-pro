import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

// Auth pages
import Login from '@/pages/Auth/Login.vue';
import Register from '@/pages/Auth/Register.vue';

// Main pages
import Dashboard from '@/pages/Dashboard.vue';
import Accounts from '@/pages/Accounts/Index.vue';
import AccountForm from '@/pages/Accounts/Form.vue';
import Cards from '@/pages/Cards/Index.vue';
import CardForm from '@/pages/Cards/Form.vue';
import CardInvoice from '@/pages/Cards/Invoice.vue';
import Transactions from '@/pages/Transactions/Index.vue';
import TransactionForm from '@/pages/Transactions/Form.vue';
import Categories from '@/pages/Categories/Index.vue';
import Recurrences from '@/pages/Recurrences/Index.vue';
import Budgets from '@/pages/Budgets/Index.vue';
import Goals from '@/pages/Goals/Index.vue';
import Charts from '@/pages/Charts/Index.vue';
import Calendar from '@/pages/Calendar/Index.vue';
import Import from '@/pages/Import/Index.vue';
import Notifications from '@/pages/Notifications/Index.vue';
import Settings from '@/pages/Settings.vue';

const routes = [
    // Auth routes (public)
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: Register,
        meta: { guest: true },
    },

    // Protected routes
    {
        path: '/',
        name: 'dashboard',
        component: Dashboard,
        meta: { auth: true },
    },

    // Accounts
    {
        path: '/accounts',
        name: 'accounts',
        component: Accounts,
        meta: { auth: true },
    },
    {
        path: '/accounts/create',
        name: 'accounts.create',
        component: AccountForm,
        meta: { auth: true },
    },
    {
        path: '/accounts/:id/edit',
        name: 'accounts.edit',
        component: AccountForm,
        meta: { auth: true },
    },

    // Cards
    {
        path: '/cards',
        name: 'cards',
        component: Cards,
        meta: { auth: true },
    },
    {
        path: '/cards/create',
        name: 'cards.create',
        component: CardForm,
        meta: { auth: true },
    },
    {
        path: '/cards/:id/edit',
        name: 'cards.edit',
        component: CardForm,
        meta: { auth: true },
    },
    {
        path: '/cards/:id/invoice',
        name: 'cards.invoice',
        component: CardInvoice,
        meta: { auth: true },
    },

    // Transactions
    {
        path: '/transactions',
        name: 'transactions',
        component: Transactions,
        meta: { auth: true },
    },
    {
        path: '/transactions/create',
        name: 'transactions.create',
        component: TransactionForm,
        meta: { auth: true },
    },
    {
        path: '/transactions/:id/edit',
        name: 'transactions.edit',
        component: TransactionForm,
        meta: { auth: true },
    },

    // Categories
    {
        path: '/categories',
        name: 'categories',
        component: Categories,
        meta: { auth: true },
    },

    // Recurrences (Recorrências)
    {
        path: '/recurrences',
        name: 'recurrences',
        component: Recurrences,
        meta: { auth: true },
    },
    {
        path: '/recurrences/suggestions',
        name: 'recurrences.suggestions',
        component: () => import('@/pages/Recurrences/Suggestions.vue'),
        meta: { auth: true },
    },

    // Budgets (Orçamentos)
    {
        path: '/budgets',
        name: 'budgets',
        component: Budgets,
        meta: { auth: true },
    },

    // Goals (Objetivos)
    {
        path: '/goals',
        name: 'goals',
        component: Goals,
        meta: { auth: true },
    },

    // Charts
    {
        path: '/charts',
        name: 'charts',
        component: Charts,
        meta: { auth: true },
    },

    // Calendar
    {
        path: '/calendar',
        name: 'calendar',
        component: Calendar,
        meta: { auth: true },
    },

    // Import
    {
        path: '/import',
        name: 'import',
        component: Import,
        meta: { auth: true },
    },

    // Notifications
    {
        path: '/notifications',
        name: 'notifications',
        component: Notifications,
        meta: { auth: true },
    },

    // Settings
    {
        path: '/settings',
        name: 'settings',
        component: Settings,
        meta: { auth: true },
    },

    // Catch all - redirect to dashboard
    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // Wait for auth check if not done yet
    if (!authStore.authChecked) {
        await authStore.checkAuth();
    }

    // Route requires authentication
    if (to.meta.auth && !authStore.isAuthenticated) {
        return next({ name: 'login' });
    }

    // Route is for guests only (login, register)
    if (to.meta.guest && authStore.isAuthenticated) {
        return next({ name: 'dashboard' });
    }

    next();
});

export default router;
