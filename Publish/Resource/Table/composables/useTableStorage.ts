/**
 * Table Storage Composable
 *
 * Manages all localStorage operations for table state including:
 * - Filter persistence
 * - View preferences (compact mode, view mode, column order)
 * - User preferences (cache, persistence toggle, row click navigation)
 *
 * This composable centralizes all storage logic for easier maintenance and updates
 */

import { ref, computed, Ref } from 'vue';

interface StoredFilters {
    perPage: number;
    filterBy: string;
    orderBy: string;
    search: string | null;
    viewMode: 'table' | 'list';
    activeFilters: Record<string, any>;
    currentAdvancedFilters: any[];
    hiddenColumns: string[];
    enabledHiddenFields?: string[];
    compactMode: boolean;
    superCompactMode: boolean;
    columnOrder: string[];
}

interface UseTableStorageOptions {
    tableTitle: string;
    defaultIdKey?: string;
    defaultFilters?: Record<string, any>;
}

export function useTableStorage(options: UseTableStorageOptions) {
    const { tableTitle, defaultIdKey = 'id', defaultFilters = {} } = options;

    // =========================================================================
    // STORAGE KEY GENERATORS
    // =========================================================================

    const filterStorageKey = computed(() =>
        `table-filters-${tableTitle.toLowerCase().replace(/\s+/g, '-')}`
    );

    const persistenceStorageKey = computed(() =>
        `table-persistence-${tableTitle.toLowerCase().replace(/\s+/g, '-')}`
    );

    const showAdvancedFiltersStorageKey = computed(() =>
        `table-show-advanced-filters-${tableTitle.toLowerCase().replace(/\s+/g, '-')}`
    );

    const rowClickNavigationStorageKey = 'table-row-click-navigation';
    const cacheEnabledStorageKey = 'table-cache-enabled';
    const headerIconSizeStorageKey = 'table-header-icon-size';

    // =========================================================================
    // SAFE STORAGE OPERATIONS
    // =========================================================================

    /**
     * Safely get item from localStorage
     */
    const getStorageItem = <T = any>(key: string, defaultValue: T | null = null): T | null => {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (error) {
            console.warn(`Failed to read localStorage item "${key}":`, error);
            return defaultValue;
        }
    };

    /**
     * Safely set item in localStorage
     */
    const setStorageItem = (key: string, value: any): boolean => {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (error) {
            console.warn(`Failed to write localStorage item "${key}":`, error);
            return false;
        }
    };

    /**
     * Safely remove item from localStorage
     */
    const removeStorageItem = (key: string): boolean => {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.warn(`Failed to remove localStorage item "${key}":`, error);
            return false;
        }
    };

    // =========================================================================
    // FILTER STORAGE OPERATIONS
    // =========================================================================

    /**
     * Get stored filters for current table
     */
    const getStoredFilters = (): StoredFilters | null => {
        return getStorageItem<StoredFilters>(filterStorageKey.value);
    };

    /**
     * Save filters to localStorage
     */
    const saveFilters = (
        perPage: number,
        filterBy: string,
        orderBy: string,
        search: string | null,
        viewMode: 'table' | 'list',
        activeFilters: Record<string, any>,
        currentAdvancedFilters: any[],
        hiddenColumns: Set<string>,
        enabledHiddenFields: Set<string>,
        compactMode: boolean,
        superCompactMode: boolean,
        columnOrder: string[]
    ): boolean => {
        const filtersToSave: StoredFilters = {
            perPage,
            filterBy,
            orderBy,
            search,
            viewMode,
            activeFilters,
            currentAdvancedFilters,
            hiddenColumns: Array.from(hiddenColumns),
            enabledHiddenFields: Array.from(enabledHiddenFields),
            compactMode,
            superCompactMode,
            columnOrder
        };

        const success = setStorageItem(filterStorageKey.value, filtersToSave);
        if (success) {
            console.log('Filters saved to storage:', filtersToSave);
        }
        return success;
    };

    /**
     * Clear stored filters
     */
    const clearFilters = (): boolean => {
        const success = removeStorageItem(filterStorageKey.value);
        if (success) {
            console.log('Stored filters cleared');
        }
        return success;
    };

    /**
     * Check if table has stored filters
     */
    const hasStoredFilters = (): boolean => {
        return getStoredFilters() !== null;
    };

    // =========================================================================
    // PERSISTENCE TOGGLE OPERATIONS
    // =========================================================================

    /**
     * Get filter persistence preference
     */
    const getPersistenceEnabled = (): boolean => {
        return getStorageItem<boolean>(persistenceStorageKey.value, false) ?? false;
    };

    /**
     * Set filter persistence preference
     */
    const setPersistenceEnabled = (enabled: boolean): boolean => {
        const success = setStorageItem(persistenceStorageKey.value, enabled);
        if (success) {
            console.log(`Filter persistence ${enabled ? 'enabled' : 'disabled'}`);
            // Clear stored filters when disabling persistence
            if (!enabled) {
                clearFilters();
            }
        }
        return success;
    };

    // =========================================================================
    // ROW CLICK NAVIGATION OPERATIONS
    // =========================================================================

    /**
     * Get row click navigation preference
     */
    const getRowClickNavigationEnabled = (): boolean => {
        return getStorageItem<boolean>(rowClickNavigationStorageKey, false) ?? false;
    };

    /**
     * Set row click navigation preference
     */
    const setRowClickNavigationEnabled = (enabled: boolean): boolean => {
        return setStorageItem(rowClickNavigationStorageKey, enabled);
    };

    // =========================================================================
    // ADVANCED FILTERS VISIBILITY OPERATIONS
    // =========================================================================

    /**
     * Get advanced filters visibility state
     */
    const getShowAdvancedFilters = (): boolean => {
        return getStorageItem<boolean>(showAdvancedFiltersStorageKey.value, false) ?? false;
    };

    /**
     * Set advanced filters visibility state
     */
    const setShowAdvancedFilters = (show: boolean): boolean => {
        return setStorageItem(showAdvancedFiltersStorageKey.value, show);
    };

    // =========================================================================
    // CACHE OPERATIONS
    // =========================================================================

    /**
     * Get cache enabled preference
     */
    const getCacheEnabled = (): boolean => {
        return getStorageItem<boolean>(cacheEnabledStorageKey, true) ?? true;
    };

    /**
     * Set cache enabled preference
     */
    const setCacheEnabled = (enabled: boolean): boolean => {
        return setStorageItem(cacheEnabledStorageKey, enabled);
    };

    // =========================================================================
    // HEADER ICON SIZE OPERATIONS
    // =========================================================================

    /**
     * Get header icon size preference
     */
    const getHeaderIconSize = (): number => {
        const size = getStorageItem<number>(headerIconSizeStorageKey, 16);
        return size ?? 16;
    };

    /**
     * Set header icon size preference
     */
    const setHeaderIconSize = (size: number): boolean => {
        return setStorageItem(headerIconSizeStorageKey, size);
    };

    // =========================================================================
    // BULK OPERATIONS
    // =========================================================================

    /**
     * Clear all table-related storage for current table
     */
    const clearAllStorage = (): boolean => {
        const operations = [
            removeStorageItem(filterStorageKey.value),
            removeStorageItem(persistenceStorageKey.value)
        ];
        const success = operations.every(op => op === true);
        if (success) {
            console.log('All table storage cleared');
        }
        return success;
    };

    /**
     * Export all table storage as JSON (for debugging/export)
     */
    const exportStorage = (): Record<string, any> => {
        return {
            filters: getStoredFilters(),
            persistenceEnabled: getPersistenceEnabled(),
            rowClickNavigationEnabled: getRowClickNavigationEnabled(),
            cacheEnabled: getCacheEnabled(),
            headerIconSize: getHeaderIconSize(),
            showAdvancedFilters: getShowAdvancedFilters(),
            table: tableTitle
        };
    };

    /**
     * Get storage usage statistics
     */
    const getStorageStats = () => {
        let totalSize = 0;
        const items: Record<string, string> = {};

        const keys = [
            filterStorageKey.value,
            persistenceStorageKey.value,
            rowClickNavigationStorageKey,
            cacheEnabledStorageKey,
            headerIconSizeStorageKey
        ];

        keys.forEach(key => {
            const item = localStorage.getItem(key);
            if (item) {
                items[key] = item;
                totalSize += item.length;
            }
        });

        return {
            itemCount: Object.keys(items).length,
            totalSizeBytes: totalSize,
            totalSizeKB: (totalSize / 1024).toFixed(2),
            items
        };
    };

    return {
        // Storage keys
        filterStorageKey,
        persistenceStorageKey,
        showAdvancedFiltersStorageKey,
        rowClickNavigationStorageKey,
        cacheEnabledStorageKey,
        headerIconSizeStorageKey,

        // Safe operations
        getStorageItem,
        setStorageItem,
        removeStorageItem,

        // Filter operations
        getStoredFilters,
        saveFilters,
        clearFilters,
        hasStoredFilters,

        // Persistence operations
        getPersistenceEnabled,
        setPersistenceEnabled,

        // Advanced filters operations
        getShowAdvancedFilters,
        setShowAdvancedFilters,

        // Row click navigation operations
        getRowClickNavigationEnabled,
        setRowClickNavigationEnabled,

        // Cache operations
        getCacheEnabled,
        setCacheEnabled,

        // Icon size operations
        getHeaderIconSize,
        setHeaderIconSize,

        // Bulk operations
        clearAllStorage,
        exportStorage,
        getStorageStats
    };
}
