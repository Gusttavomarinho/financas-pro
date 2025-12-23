<template>
    <div>
        <div class="mb-6">
            <RouterLink to="/cards" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Voltar
            </RouterLink>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ isEditing ? 'Editar Cartão' : 'Novo Cartão' }}
            </h1>
        </div>

        <form @submit.prevent="handleSubmit" class="card max-w-2xl">
            <div class="space-y-6">
                <!-- Card Name -->
                <div>
                    <label for="name" class="label">Nome do cartão *</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="input"
                        placeholder="Ex: Nubank Gold, Itaú Platinum..."
                    />
                </div>

                <!-- Linked Account -->
                <div>
                    <label for="account_id" class="label">Conta vinculada *</label>
                    <select id="account_id" v-model="form.account_id" required class="input">
                        <option value="">Selecione a conta para pagamento...</option>
                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                            {{ account.name }} - {{ account.bank || 'Sem banco' }} ({{ formatCurrency(account.current_balance) }})
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">A fatura será paga através desta conta</p>
                </div>

                <!-- Brand -->
                <div>
                    <label for="brand" class="label">Bandeira *</label>
                    <select id="brand" v-model="form.brand" required class="input">
                        <option value="">Selecione...</option>
                        <option value="visa">Visa</option>
                        <option value="mastercard">Mastercard</option>
                        <option value="elo">Elo</option>
                        <option value="amex">American Express</option>
                        <option value="hipercard">Hipercard</option>
                        <option value="diners">Diners Club</option>
                        <option value="discover">Discover</option>
                    </select>
                </div>


                <!-- Holder and Last 4 digits -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="holder_name" class="label">Nome do titular *</label>
                        <input
                            id="holder_name"
                            v-model="form.holder_name"
                            type="text"
                            required
                            class="input uppercase"
                            placeholder="NOME NO CARTÃO"
                        />
                    </div>
                    <div>
                        <label for="last_4_digits" class="label">Últimos 4 dígitos *</label>
                        <input
                            id="last_4_digits"
                            v-model="form.last_4_digits"
                            type="text"
                            required
                            maxlength="4"
                            pattern="\d{4}"
                            class="input text-center font-mono tracking-widest"
                            placeholder="1234"
                        />
                    </div>
                </div>

                <!-- Validity and Type -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="valid_thru" class="label">Validade *</label>
                        <ExpiryInput v-model="form.valid_thru" />
                    </div>
                    <div>
                        <label for="type" class="label">Tipo *</label>
                        <select id="type" v-model="form.type" required class="input">
                            <option value="credito">Crédito</option>
                            <option value="debito">Débito</option>
                            <option value="hibrido">Crédito e Débito</option>
                        </select>
                    </div>
                </div>

                <!-- Credit Limit -->
                <div>
                    <label for="credit_limit" class="label">Limite de crédito *</label>
                    <MoneyInput v-model="form.credit_limit" />
                </div>

                <!-- Closing and Due Days -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="closing_day" class="label">Dia de fechamento *</label>
                        <select id="closing_day" v-model="form.closing_day" required class="input">
                            <option v-for="day in 31" :key="day" :value="day">
                                Dia {{ day }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Dia em que a fatura fecha</p>
                    </div>
                    <div>
                        <label for="due_day" class="label">Dia de vencimento *</label>
                        <select id="due_day" v-model="form.due_day" required class="input">
                            <option v-for="day in 31" :key="day" :value="day">
                                Dia {{ day }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Dia em que a fatura vence</p>
                    </div>
                </div>

                <!-- Color -->
                <div>
                    <ColorPicker v-model="form.color" label="Cor do cartão" />
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="label">Observações</label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        class="input"
                        rows="2"
                        placeholder="Notas adicionais..."
                    />
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit" class="btn-primary flex-1" :disabled="loading">
                        <span v-if="loading">Salvando...</span>
                        <span v-else>{{ isEditing ? 'Salvar Alterações' : 'Criar Cartão' }}</span>
                    </button>
                    <RouterLink to="/cards" class="btn-secondary">
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
import { useCardsStore } from '@/stores/cards';
import { useAccountsStore } from '@/stores/accounts';
import { useAuthStore } from '@/stores/auth';
import MoneyInput from '@/components/Common/MoneyInput.vue';
import ExpiryInput from '@/components/Common/ExpiryInput.vue';
import ColorPicker from '@/components/Common/ColorPicker.vue';

const route = useRoute();
const router = useRouter();
const cardsStore = useCardsStore();
const accountsStore = useAccountsStore();
const authStore = useAuthStore();

const isEditing = computed(() => !!route.params.id);
const loading = ref(false);
const errors = ref({});

const accounts = computed(() => accountsStore.accounts.filter(a => a.status !== 'archived'));

const form = reactive({
    name: '',
    account_id: '',
    brand: '',
    holder_name: '',
    last_4_digits: '',
    valid_thru: '',
    type: 'credito',
    credit_limit: 0,
    closing_day: 25,
    due_day: 10,
    color: '#1f2937',
    notes: '',
});

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value || 0);
}

async function handleSubmit() {
    loading.value = true;
    errors.value = {};
    
    try {
        let result;
        if (isEditing.value) {
            result = await cardsStore.updateCard(route.params.id, form);
        } else {
            result = await cardsStore.createCard(form);
        }
        
        if (result.success) {
            router.push('/cards');
        } else if (result.errors) {
            errors.value = result.errors;
        }
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await accountsStore.fetchAccounts();
    
    // Preenche nome do titular com o nome do usuário logado
    if (!isEditing.value && authStore.user) {
        form.holder_name = authStore.user.name.toUpperCase();
    }
    
    if (isEditing.value) {
        loading.value = true;
        try {
            const card = await cardsStore.fetchCard(route.params.id);
            if (card) {
                form.name = card.name || '';
                form.account_id = card.account_id || '';
                form.brand = card.brand || '';
                form.holder_name = card.holder_name || '';
                form.last_4_digits = card.last_4_digits || '';
                form.valid_thru = card.valid_thru || '';
                form.type = card.type || 'credito';
                form.credit_limit = parseFloat(card.credit_limit) || 0;
                form.closing_day = parseInt(card.closing_day) || 25;
                form.due_day = parseInt(card.due_day) || 10;
                form.color = card.color || '#1f2937';
                form.notes = card.notes || '';
            }
        } finally {
            loading.value = false;
        }
    }
});
</script>
