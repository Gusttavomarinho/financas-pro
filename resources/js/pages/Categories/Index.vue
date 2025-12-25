<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Categorias</h1>
                <p class="text-gray-500 dark:text-gray-400">Organize seus lançamentos por categoria</p>
            </div>
            <button @click="openCreateModal" class="btn-primary">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nova Categoria
            </button>
        </div>

        <!-- Dismissable info banner -->
        <DismissableBanner storage-key="categories-info" color="yellow">
            Categorias ajudam a organizar seus lançamentos de receitas e despesas. Use-as também para definir orçamentos mensais ou anuais.
        </DismissableBanner>

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
                        @click="navigateToTransactions(category)"
                        class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer group"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-lg flex items-center justify-center"
                                :style="{ backgroundColor: category.color || '#22c55e' }"
                            >
                                <span class="text-white text-sm">{{ category.icon || '💰' }}</span>
                            </div>
                            <span class="font-medium text-gray-900 dark:text-white">{{ category.name }}</span>
                            <span v-if="category.transactions_count > 0" class="text-xs text-gray-400">
                                {{ category.transactions_count }} {{ category.transactions_count === 1 ? 'uso' : 'usos' }}
                            </span>
                        </div>
                        <div class="flex items-center">
                            <button
                                @click.stop="handleEdit(category)"
                                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-gray-400 hover:text-blue-500"
                                title="Editar"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            <button
                                @click.stop="handleDelete(category)"
                                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-gray-400 hover:text-red-500"
                                title="Excluir"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
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
                        @click="navigateToTransactions(category)"
                        class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer group"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-lg flex items-center justify-center"
                                :style="{ backgroundColor: category.color || '#ef4444' }"
                            >
                                <span class="text-white text-sm">{{ category.icon || '📦' }}</span>
                            </div>
                            <span class="font-medium text-gray-900 dark:text-white">{{ category.name }}</span>
                            <span v-if="category.transactions_count > 0" class="text-xs text-gray-400">
                                {{ category.transactions_count }} {{ category.transactions_count === 1 ? 'uso' : 'usos' }}
                            </span>
                        </div>
                        <div class="flex items-center">
                            <button
                                @click.stop="handleEdit(category)"
                                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-gray-400 hover:text-blue-500"
                                title="Editar"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            <button
                                @click.stop="handleDelete(category)"
                                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-gray-400 hover:text-red-500"
                                title="Excluir"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        {{ isEditing ? 'Editar Categoria' : 'Nova Categoria' }}
                    </h3>
                    
                    <form @submit.prevent="handleSave" class="space-y-4">
                        <div>
                            <label class="label">Nome *</label>
                            <input v-model="categoryForm.name" type="text" required class="input" placeholder="Nome da categoria" />
                        </div>

                        <div>
                            <label class="label">Tipo *</label>
                            <select v-model="categoryForm.type" required class="input" :disabled="isEditing">
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
                                    @click="categoryForm.color = color"
                                    :class="[
                                        'w-8 h-8 rounded-lg transition-transform',
                                        categoryForm.color === color ? 'ring-2 ring-offset-2 ring-gray-900 scale-110' : ''
                                    ]"
                                    :style="{ backgroundColor: color }"
                                />
                            </div>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button type="submit" class="btn-primary flex-1">
                                {{ isEditing ? 'Salvar' : 'Criar' }}
                            </button>
                            <button type="button" @click="showModal = false" class="btn-secondary">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-sm animate-slide-up">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Excluir categoria?</h3>
                    </div>
                    <div class="text-gray-600 dark:text-gray-400 mb-6 space-y-2">
                        <p>Esta categoria possui <strong>{{ categoryToDelete?.transactions_count }} lançamentos</strong> associados.</p>
                        <p>Ao excluir, esses lançamentos ficarão como <strong>"Sem categoria"</strong>.</p>
                        <p>Deseja continuar?</p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="showDeleteModal = false" class="btn-secondary flex-1">
                            Cancelar
                        </button>
                        <button @click="confirmDelete" class="btn-danger flex-1">
                            Excluir mesmo assim
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
// Import transactions store
import { useCategoriesStore } from '@/stores/categories';
import DismissableBanner from '@/components/Common/DismissableBanner.vue';
import { useTransactionsStore } from '@/stores/transactions';

const categoriesStore = useCategoriesStore();
const transactionsStore = useTransactionsStore();
const showModal = ref(false);
const showDeleteModal = ref(false);
const categoryToDelete = ref(null);
const isEditing = ref(false);
const editingId = ref(null);

const colors = [
    '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
    '#22c55e', '#14b8a6', '#06b6d4', '#3b82f6', '#6366f1',
    '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#6b7280',
];

const categoryForm = reactive({
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

function openCreateModal() {
    isEditing.value = false;
    editingId.value = null;
    categoryForm.name = '';
    categoryForm.type = 'despesa';
    categoryForm.color = colors[0];
    showModal.value = true;
}

function handleEdit(category) {
    isEditing.value = true;
    editingId.value = category.id;
    categoryForm.name = category.name;
    categoryForm.type = category.type;
    categoryForm.color = category.color || colors[0];
    showModal.value = true;
}

async function handleSave() {
    let result;
    if (isEditing.value) {
        result = await categoriesStore.updateCategory(editingId.value, categoryForm);
    } else {
        result = await categoriesStore.createCategory(categoryForm);
    }

    if (result.success) {
        showModal.value = false;
        // Reset form
        categoryForm.name = '';
        categoryForm.type = 'despesa';
        categoryForm.color = colors[0];
    }
}

async function handleDelete(category) {
    if (!category.transactions_count || category.transactions_count === 0) {
        if (confirm('Excluir categoria?')) {
            await categoriesStore.deleteCategory(category.id);
        }
        return;
    }
    categoryToDelete.value = category;
    showDeleteModal.value = true;
}

async function confirmDelete() {
    if (categoryToDelete.value) {
        await categoriesStore.deleteCategory(categoryToDelete.value.id);
        showDeleteModal.value = false;
        categoryToDelete.value = null;
    }
}

onMounted(() => {
    categoriesStore.fetchCategories();
});

const router = useRouter();

function navigateToTransactions(category) {
    // Navigate to transactions filtered by this category
    // Clear existing filters first to ensure we don't carry over date filters
    transactionsStore.clearFilters();
    
    router.push({
        path: '/transactions',
        query: {
            category_id: category.id,
            type: category.type
        }
    });
}

</script>
