<template>
    <div class="relative">
        <input
            ref="inputRef"
            type="text"
            :value="formattedValue"
            @input="handleInput"
            @keydown="handleKeydown"
            class="input text-center font-mono tracking-wider"
            :class="inputClass"
            inputmode="numeric"
            placeholder="MM/AAAA"
            maxlength="7"
        />
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    inputClass: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue']);

const rawValue = ref(props.modelValue || '');

const formattedValue = computed(() => {
    return rawValue.value;
});

watch(() => props.modelValue, (newValue) => {
    if (newValue !== rawValue.value) {
        rawValue.value = newValue || '';
    }
});

function handleInput(event) {
    let value = event.target.value;
    
    // Remove tudo que não é número ou /
    let numbers = value.replace(/[^\d]/g, '');
    
    // Limita a 6 dígitos (MMAAAA)
    if (numbers.length > 6) {
        numbers = numbers.slice(0, 6);
    }
    
    // Formata como MM/AAAA
    let formatted = '';
    if (numbers.length > 0) {
        formatted = numbers.slice(0, 2);
        if (numbers.length > 2) {
            formatted += '/' + numbers.slice(2);
        }
    }
    
    rawValue.value = formatted;
    emit('update:modelValue', formatted);
    
    event.target.value = formatted;
}

function handleKeydown(event) {
    // Permite backspace, delete, tab, escape, enter
    if (
        event.key === 'Backspace' ||
        event.key === 'Delete' ||
        event.key === 'Tab' ||
        event.key === 'Escape' ||
        event.key === 'Enter' ||
        event.key === 'ArrowLeft' ||
        event.key === 'ArrowRight'
    ) {
        return;
    }
    
    // Bloqueia se não for número
    if (!/^\d$/.test(event.key)) {
        event.preventDefault();
    }
}
</script>
