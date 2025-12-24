import { ref, watch, onMounted, nextTick, isRef } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { debounce } from 'lodash';

/**
 * useFilters Composable
 * 
 * Sincroniza um objeto reativo de filtros com a URL (query params).
 * - Lê da URL ao iniciar
 * - Escreve na URL quando filtros mudam
 * 
 * @param {Object} storeFilters - Ref ou Reactive object contendo os filtros
 * @param {Function} refreshCallback - Função para recarregar dados quando filtro mudar
 * @param {Object} defaultFilters - Valores padrão para ignorar na URL
 */
export function useFilters(storeFilters, refreshCallback, defaultFilters = {}) {
    const router = useRouter();
    const route = useRoute();
    const isInitialized = ref(false);
    const skipNextUrlUpdate = ref(false);

    // Helper to get/set the filters object (handles both ref and reactive)
    const getFilters = () => {
        return isRef(storeFilters) ? storeFilters.value : storeFilters;
    };

    const setFilter = (key, value) => {
        const filters = getFilters();
        if (filters) {
            filters[key] = value;
        }
    };

    // Atualiza URL quando store muda
    const updateUrl = debounce((newFilters) => {
        if (!isInitialized.value) return;
        if (skipNextUrlUpdate.value) {
            skipNextUrlUpdate.value = false;
            return;
        }

        const query = {};

        // Only include filters that differ from defaults
        Object.keys(defaultFilters).forEach(key => {
            const value = newFilters[key];
            const defaultValue = defaultFilters[key];

            // Skip non-primitive values
            if (typeof value === 'object' && value !== null) {
                return;
            }

            if (value !== defaultValue && value !== null && value !== undefined && value !== '') {
                query[key] = String(value);
            }
        });

        router.replace({ query });
        refreshCallback();
    }, 300);

    // Observa mudanças profundas nos filtros do store
    watch(
        () => getFilters(),
        (newFilters) => {
            updateUrl(newFilters);
        },
        { deep: true }
    );

    // Mapeia query params para o store na montagem
    onMounted(async () => {
        const query = route.query;
        const currentFilters = getFilters();
        const newFilters = { ...currentFilters };
        let hasChanges = false;


        // Handle special month/year params (from Dashboard navigation)
        if (query.month && query.year) {
            const year = parseInt(query.year);
            const month = parseInt(query.month);
            // First day of month
            const firstDay = new Date(year, month - 1, 1);
            // Last day of month
            const lastDay = new Date(year, month, 0);

            newFilters.date_from = firstDay.toISOString().split('T')[0];
            newFilters.date_to = lastDay.toISOString().split('T')[0];
            hasChanges = true;
        }

        // Apply type filter from URL if present
        if (query.type) {
            newFilters.type = query.type;
            hasChanges = true;
        }

        // Apply other standard filters from URL
        Object.keys(defaultFilters).forEach(key => {
            // Skip if already handled above
            if (key === 'type') return;
            if ((key === 'date_from' || key === 'date_to') && query.month && query.year) return;

            // Apply from URL if present
            if (query[key] !== undefined && query[key] !== '') {
                newFilters[key] = query[key];
                hasChanges = true;
            }
        });


        // Apply filters to store
        if (hasChanges) {
            skipNextUrlUpdate.value = true;
            // Mutate individual properties
            Object.keys(newFilters).forEach(key => {
                setFilter(key, newFilters[key]);
            });
        }

        // Wait for reactivity to settle
        await nextTick();

        // Now enable URL syncing
        isInitialized.value = true;

        // Do initial data fetch
        refreshCallback();
    });

    return {
        isInitialized
    };
}
