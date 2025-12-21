<template>
    <div>
        <div class="mb-6">
            <RouterLink to="/accounts" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Voltar
            </RouterLink>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ isEditing ? 'Editar Conta' : 'Nova Conta' }}
            </h1>
        </div>

        <form @submit.prevent="handleSubmit" class="card max-w-2xl">
            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="label">Nome da conta *</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        :class="['input', errors.name && 'input-error']"
                        placeholder="Ex: Nubank, Itaú, Carteira..."
                    />
                    <p v-if="errors.name" class="mt-1 text-sm text-danger-500">{{ errors.name[0] }}</p>
                </div>

                <!-- Type -->
                <div>
                    <label for="type" class="label">Tipo de conta *</label>
                    <select id="type" v-model="form.type" required class="input">
                        <option value="">Selecione...</option>
                        <option value="corrente">Conta Corrente</option>
                        <option value="poupanca">Poupança</option>
                        <option value="carteira_digital">Carteira Digital</option>
                        <option value="investimento">Investimento</option>
                        <option value="caixa">Caixa</option>
                        <option value="credito">Crédito</option>
                    </select>
                </div>

                <!-- Initial balance -->
                <div>
                    <label for="initial_balance" class="label">Saldo inicial</label>
                    <MoneyInput v-model="form.initial_balance" />
                </div>

                <!-- Bank -->
                <div>
                    <label for="bank" class="label">Banco/Instituição</label>
                    <input
                        id="bank"
                        v-model="form.bank"
                        type="text"
                        class="input"
                        placeholder="Ex: Nubank, Itaú, Bradesco..."
                        list="banks"
                    />
                    <datalist id="banks">
                        <option value="Nubank" />
                        <option value="Itaú" />
                        <option value="Bradesco" />
                        <option value="Santander" />
                        <option value="Banco do Brasil" />
                        <option value="Caixa Econômica" />
                        <option value="Inter" />
                        <option value="C6 Bank" />
                        <option value="PicPay" />
                        <option value="Mercado Pago" />
                        <option value="Original" />
                        <option value="Next" />
                        <option value="Neon" />
                    </datalist>
                </div>

                <!-- Color -->
                <div>
                    <label class="label">Cor</label>
                    <ColorPicker v-model="form.color" />
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="label">Observações</label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        class="input"
                        rows="3"
                        placeholder="Notas adicionais..."
                    />
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit" class="btn-primary flex-1" :disabled="loading">
                        <span v-if="loading">Salvando...</span>
                        <span v-else>{{ isEditing ? 'Salvar Alterações' : 'Criar Conta' }}</span>
                    </button>
                    <RouterLink to="/accounts" class="btn-secondary">
                        Cancelar
                    </RouterLink>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useAccountsStore } from '@/stores/accounts';
import MoneyInput from '@/components/Common/MoneyInput.vue';
import ColorPicker from '@/components/Common/ColorPicker.vue';

const route = useRoute();
const router = useRouter();
const accountsStore = useAccountsStore();

const isEditing = computed(() => !!route.params.id);
const errors = ref({});
const loading = ref(false);

const form = reactive({
    name: '',
    type: '',
    initial_balance: 0,
    bank: '',
    color: '#22c55e',
    notes: '',
});

async function handleSubmit() {
    errors.value = {};
    loading.value = true;
    
    try {
        let result;
        if (isEditing.value) {
            result = await accountsStore.updateAccount(route.params.id, form);
        } else {
            result = await accountsStore.createAccount(form);
        }
        
        if (result.success) {
            router.push('/accounts');
        } else if (result.errors) {
            errors.value = result.errors;
        }
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    if (isEditing.value) {
        loading.value = true;
        try {
            const account = await accountsStore.fetchAccount(route.params.id);
            if (account) {
                form.name = account.name || '';
                form.type = account.type || '';
                form.initial_balance = parseFloat(account.initial_balance) || 0;
                form.bank = account.bank || '';
                form.color = account.color || '#22c55e';
                form.notes = account.notes || '';
            }
        } finally {
            loading.value = false;
        }
    }
});
</script>
