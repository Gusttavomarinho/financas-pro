<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Categorias</h1>
                <p class="text-gray-500 dark:text-gray-400">Organize seus lançamentos por categoria</p>
            </div>
            <button @click="showCreateModal = true" class="btn-primary">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nova Categoria
            </button>
        </div>

        <!-- Categories list -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Income categories -->
            <div class="card">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                    Categorias de Receita
                </h3>
                <div class="space-y-2">
                    <div
                        v-for="category in incomeCategories"
                        :key="category.id"
                        class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/50"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-lg flex items-center justify-center"
                                :style="{ backgroundColor: category.color || '#22c55e' }"
                            >
                                <span class="text-white text-sm">{{ category.icon || '💰' }}</span>
                            </div>
                            <span class="font-medium text-gray-900 dark:text-white">{{ category.name }}</span>
                        </div>
                        <button
                            @click="handleDelete(category.id)"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                        >
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Expense categories -->
            <div class="card">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                    Categorias de Despesa
                </h3>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <div
                        v-for="category in expenseCategories"
                        :key="category.id"
                        class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/50"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-lg flex items-center justify-center"
                                :style="{ backgroundColor: category.color || '#ef4444' }"
                            >
                                <span class="text-white text-sm">{{ category.icon || '📦' }}</span>
                            </div>
                            <span class="font-medium text-gray-900 dark:text-white">{{ category.name }}</span>
                        </div>
                        <button
                            @click="handleDelete(category.id)"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                        >
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create modal -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Nova Categoria</h3>
                    
                    <form @submit.prevent="handleCreate" class="space-y-4">
                        <div>
                            <label class="label">Nome *</label>
                            <input v-model="newCategory.name" type="text" required class="input" placeholder="Nome da categoria" />
                        </div>

                        <div>
                            <label class="label">Tipo *</label>
                            <select v-model="newCategory.type" required class="input">
                                <option value="receita">Receita</option>
                                <option value="despesa">Despesa</option>
                            </select>
                        </div>

                        <div>
                            <label class="label">Cor</label>
                            <div class="flex gap-2 flex-wrap">
                                <button
                                    v-for="color in colors"
                                    :key="color"
                                    type="button"
                                    @click="newCategory.color = color"
                                    :class="[
                                        'w-8 h-8 rounded-lg transition-transform',
                                        newCategory.color === color ? 'ring-2 ring-offset-2 ring-gray-900 scale-110' : ''
                                    ]"
                                    :style="{ backgroundColor: color }"
                                />
                            </div>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button type="submit" class="btn-primary flex-1">Criar</button>
                            <button type="button" @click="showCreateModal = false" class="btn-secondary">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useCategoriesStore } from '@/stores/categories';

const categoriesStore = useCategoriesStore();
const showCreateModal = ref(false);

const colors = [
    '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
    '#22c55e', '#14b8a6', '#06b6d4', '#3b82f6', '#6366f1',
    '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#6b7280',
];

const newCategory = reactive({
    name: '',
    type: 'despesa',
    color: colors[0],
});

const incomeCategories = computed(() => 
    categoriesStore.categories.filter(c => c.type === 'receita')
);

const expenseCategories = computed(() => 
    categoriesStore.categories.filter(c => c.type === 'despesa')
);

async function handleCreate() {
    const result = await categoriesStore.createCategory(newCategory);
    if (result.success) {
        showCreateModal.value = false;
        newCategory.name = '';
        newCategory.type = 'despesa';
        newCategory.color = colors[0];
    }
}

async function handleDelete(id) {
    if (confirm('Tem certeza que deseja excluir esta categoria?')) {
        await categoriesStore.deleteCategory(id);
    }
}

onMounted(() => {
    categoriesStore.fetchCategories();
});
</script>
