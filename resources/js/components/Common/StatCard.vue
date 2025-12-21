<template>
    <div :class="['stat-card transition-all', clickable ? 'hover:shadow-lg hover:scale-[1.02] cursor-pointer' : '']">
        <div class="flex items-start justify-between">
            <div>
                <p class="stat-label">{{ title }}</p>
                <p :class="['stat-value', colorClass]">{{ value }}</p>
            </div>
            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', bgColorClass]">
                <!-- Wallet icon -->
                <svg v-if="icon === 'wallet'" class="w-6 h-6" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <!-- Arrow up icon -->
                <svg v-else-if="icon === 'arrow-up'" class="w-6 h-6" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                </svg>
                <!-- Arrow down icon -->
                <svg v-else-if="icon === 'arrow-down'" class="w-6 h-6" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                </svg>
                <!-- Credit card icon -->
                <svg v-else-if="icon === 'credit-card'" class="w-6 h-6" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
            </div>
        </div>
        
        <div v-if="trend !== undefined" class="mt-3 flex items-center gap-1">
            <svg
                v-if="trend >= 0"
                class="w-4 h-4 text-green-500"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
            </svg>
            <svg
                v-else
                class="w-4 h-4 text-red-500"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
            </svg>
            <span :class="['text-sm font-medium', trend >= 0 ? 'text-green-500' : 'text-red-500']">
                {{ Math.abs(trend) }}%
            </span>
            <span class="text-sm text-gray-500">vs mês anterior</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    value: { type: String, required: true },
    icon: { type: String, default: 'wallet' },
    trend: { type: Number, default: undefined },
    color: { type: String, default: 'primary' },
    clickable: { type: Boolean, default: false },
});

const colorClass = computed(() => {
    const colors = {
        primary: 'text-gray-900 dark:text-white',
        green: 'text-green-600 dark:text-green-400',
        red: 'text-red-600 dark:text-red-400',
        yellow: 'text-yellow-600 dark:text-yellow-400',
    };
    return colors[props.color] || colors.primary;
});

const bgColorClass = computed(() => {
    const colors = {
        primary: 'bg-primary-100 dark:bg-primary-900/30',
        green: 'bg-green-100 dark:bg-green-900/30',
        red: 'bg-red-100 dark:bg-red-900/30',
        yellow: 'bg-yellow-100 dark:bg-yellow-900/30',
    };
    return colors[props.color] || colors.primary;
});

const iconColorClass = computed(() => {
    const colors = {
        primary: 'text-primary-600 dark:text-primary-400',
        green: 'text-green-600 dark:text-green-400',
        red: 'text-red-600 dark:text-red-400',
        yellow: 'text-yellow-600 dark:text-yellow-400',
    };
    return colors[props.color] || colors.primary;
});
</script>
