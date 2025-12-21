<template>
    <div class="relative">
        <label v-if="label" class="label">{{ label }}</label>
        <button
            type="button"
            @click="showPicker = !showPicker"
            class="w-full h-12 rounded-lg border-2 transition-all hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-primary-500 flex items-center gap-3 px-3"
            :style="{ borderColor: currentColor || '#d1d5db' }"
        >
            <div
                class="w-8 h-8 rounded-md border border-gray-300"
                :style="{ backgroundColor: currentColor || '#e5e7eb' }"
            ></div>
            <span class="text-sm" :class="currentColor ? 'text-gray-900 dark:text-white' : 'text-gray-400'">
                {{ currentColor || 'Selecione uma cor...' }}
            </span>
        </button>
        
        <!-- Color Picker Popup -->
        <div
            v-if="showPicker"
            class="absolute z-50 mt-2 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700"
            @click.stop
        >
            <!-- Preset Colors -->
            <div class="grid grid-cols-6 gap-2 mb-4">
                <button
                    v-for="color in presetColors"
                    :key="color"
                    type="button"
                    @click="selectColor(color)"
                    class="w-8 h-8 rounded-full border-2 transition-transform hover:scale-110 focus:outline-none"
                    :class="currentColor === color ? 'ring-2 ring-primary-500 ring-offset-2' : 'border-transparent'"
                    :style="{ backgroundColor: color }"
                />
            </div>
            
            <!-- Custom Color Picker -->
            <div class="space-y-3">
                <label class="label text-xs">Cor personalizada</label>
                <div class="flex items-center gap-3">
                    <input
                        type="color"
                        :value="currentColor || '#22c55e'"
                        @input="selectColor($event.target.value)"
                        class="w-12 h-10 rounded cursor-pointer border-0 p-0"
                    />
                    <input
                        type="text"
                        :value="currentColor"
                        @input="selectColor($event.target.value)"
                        placeholder="#000000"
                        class="input flex-1 font-mono text-sm"
                        maxlength="7"
                    />
                </div>
            </div>
            
            <!-- Close button -->
            <button
                type="button"
                @click="showPicker = false"
                class="mt-3 w-full btn-secondary text-sm"
            >
                Fechar
            </button>
        </div>
        
        <!-- Backdrop -->
        <div
            v-if="showPicker"
            class="fixed inset-0 z-40"
            @click="showPicker = false"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    label: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue']);

const showPicker = ref(false);

// Use um valor padrão inicial se não houver modelValue
const currentColor = computed(() => props.modelValue || '');

const presetColors = [
    // Greens
    '#22c55e', '#16a34a', '#15803d',
    // Blues
    '#3b82f6', '#2563eb', '#1d4ed8',
    // Purples
    '#8b5cf6', '#7c3aed', '#6d28d9',
    // Pinks
    '#ec4899', '#db2777', '#be185d',
    // Reds
    '#ef4444', '#dc2626', '#b91c1c',
    // Oranges
    '#f97316', '#ea580c', '#c2410c',
    // Yellows
    '#eab308', '#ca8a04', '#a16207',
    // Grays
    '#6b7280', '#4b5563', '#374151',
];

function selectColor(color) {
    emit('update:modelValue', color);
    showPicker.value = false;
}

// Definir cor padrão se não houver valor
onMounted(() => {
    if (!props.modelValue) {
        emit('update:modelValue', presetColors[0]);
    }
});
</script>
