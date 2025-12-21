<template>
    <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm pointer-events-none">
            R$
        </span>
        <input
            ref="inputRef"
            type="text"
            :value="formattedValue"
            @input="handleInput"
            @blur="handleBlur"
            @focus="handleFocus"
            class="input pl-10 text-right font-mono"
            :class="inputClass"
            inputmode="numeric"
            placeholder="0,00"
        />
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Number, String],
        default: 0
    },
    inputClass: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue']);

const inputRef = ref(null);
const rawValue = ref(Math.round((props.modelValue || 0) * 100)); // Armazena em centavos

// Formata o valor para exibição (0,00)
const formattedValue = computed(() => {
    const value = rawValue.value / 100;
    return value.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
});

// Quando o modelValue externo muda, atualiza o valor interno
watch(() => props.modelValue, (newValue) => {
    const centavos = Math.round((newValue || 0) * 100);
    if (centavos !== rawValue.value) {
        rawValue.value = centavos;
    }
});

function handleInput(event) {
    // Remove tudo que não é número
    let numbers = event.target.value.replace(/\D/g, '');
    
    // Limita a 12 dígitos (999.999.999,99)
    if (numbers.length > 12) {
        numbers = numbers.slice(0, 12);
    }
    
    // Converte para número inteiro (centavos)
    rawValue.value = parseInt(numbers, 10) || 0;
    
    // Emite o valor em reais (dividido por 100)
    emit('update:modelValue', rawValue.value / 100);
    
    // Atualiza o input com o valor formatado
    event.target.value = formattedValue.value;
}

function handleBlur(event) {
    event.target.value = formattedValue.value;
}

function handleFocus(event) {
    // Seleciona todo o texto ao focar
    setTimeout(() => {
        event.target.select();
    }, 0);
}
</script>
