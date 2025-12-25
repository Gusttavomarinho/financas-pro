<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-100 dark:border-gray-700 font-sans">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">Evolução Mensal de Receitas</h3>
        
        <div v-if="loading" class="h-64 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>

        <div v-else-if="!hasData" class="h-64 flex flex-col items-center justify-center text-gray-400">
            <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            <p>Sem histórico de receitas no período</p>
        </div>

        <div v-else class="h-64">
            <BaseChart 
                type="line"
                :data="chartData"
                :options="chartOptions"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import BaseChart from './BaseChart.vue';

const props = defineProps({
    filters: {
        type: Object,
        required: true
    }
});

const loading = ref(false);
const rawData = ref([]);

const hasData = computed(() => {
    return rawData.value && rawData.value.length > 0 && rawData.value.some(item => item.receita > 0);
});

const chartData = computed(() => {
    return {
        labels: rawData.value.map(item => item.month),
        datasets: [
            {
                label: 'Receitas',
                data: rawData.value.map(item => item.receita),
                borderColor: '#10B981', // Green-500
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }
        ]
    };
});

const chartOptions = {
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }
                    if (context.parsed.y !== null) {
                        label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                    }
                    return label;
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: '#f3f4f6'
            },
            ticks: {
                callback: function(value) {
                    return new Intl.NumberFormat('pt-BR', { 
                        style: 'currency', 
                        currency: 'BRL',
                        notation: 'compact'
                    }).format(value);
                }
            }
        },
        x: {
            grid: {
                display: false
            }
        }
    }
};

async function fetchData() {
    loading.value = true;
    try {
        const response = await axios.get('/api/reports/monthly-evolution', { params: props.filters });
        rawData.value = response.data.data || [];
    } catch (error) {
        console.error('Error fetching income evolution:', error);
        rawData.value = [];
    } finally {
        loading.value = false;
    }
}

watch(() => props.filters, fetchData, { deep: true });
onMounted(fetchData);
</script>
