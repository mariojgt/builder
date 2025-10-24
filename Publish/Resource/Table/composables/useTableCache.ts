/**
 * Table Cache Composable
 *
 * Provides client-side caching functionality for table data
 * to improve performance and reduce server load.
 *
 * Features:
 * - localStorage-based caching
 * - Automatic cache invalidation based on timestamps
 * - Page-specific cache keys
 * - Toggle cache on/off
 * - Automatic cache clearing when disabled
 */

import { ref, Ref } from 'vue';

interface CachedData {
    responseData: any;
    timestamp: number;
    cachedAt: number;
}

interface CacheOptions {
    enabled?: boolean;
    storageKey?: string;
}

export function useTableCache(options: CacheOptions = {}) {
    // ✨ Cache enabled state - reads from localStorage
    const cacheEnabled: Ref<boolean> = ref((() => {
        const stored = localStorage.getItem('table-cache-enabled');
        return stored !== null ? JSON.parse(stored) : (options.enabled ?? true);
    })());

    /**
     * Generate a unique cache key based on request parameters
     *
     * @param payload - The request payload
     * @param cacheKey - Base cache key (usually table name)
     * @param endpoint - The API endpoint URL
     * @returns Cache key string or empty string if caching is disabled
     */
    const generateCacheKey = (payload: any, cacheKey: string, endpoint: string): string => {
        // Don't cache if cache is disabled
        if (!cacheEnabled.value) {
            return '';
        }

        // Don't cache if there are filters or search active
        if (payload.search || (payload.filters && Object.keys(payload.filters).length > 0)) {
            return '';
        }

        // Extract page number from endpoint URL
        const pageMatch = endpoint.match(/[?&]page=(\d+)/);
        const page = pageMatch ? pageMatch[1] : '1';

        // Create a deterministic key from the payload
        const keyParts = [
            'builder-table',
            cacheKey,
            page, // Include page number in cache key
            payload.perPage,
            payload.sort,
            payload.direction,
            JSON.stringify(payload.modelScopes || []),
            JSON.stringify(payload.advancedFilters || [])
        ];

        return keyParts.join('-');
    };

    /**
     * Get cached data from localStorage
     *
     * @param cacheKey - The cache key to retrieve
     * @returns Cached data object or null if not found/invalid
     */
    const getCachedData = (cacheKey: string): CachedData | null => {
        if (!cacheKey) return null;

        try {
            const cached = localStorage.getItem(cacheKey);
            if (!cached) return null;

            const parsed = JSON.parse(cached);
            return parsed;
        } catch (e) {
            console.warn('Failed to parse cached data:', e);
            return null;
        }
    };

    /**
     * Store data in cache
     *
     * @param cacheKey - The cache key to use
     * @param responseData - The complete response data to cache
     * @param timestamp - Server timestamp for cache invalidation
     */
    const setCachedData = (cacheKey: string, responseData: any, timestamp: number): void => {
        if (!cacheKey) return;

        try {
            const cacheData: CachedData = {
                responseData: responseData, // Store complete response with pagination
                timestamp: timestamp,
                cachedAt: Date.now()
            };
            localStorage.setItem(cacheKey, JSON.stringify(cacheData));
        } catch (e) {
            console.warn('Failed to cache data:', e);
        }
    };

    /**
     * Clear all table cache entries from localStorage
     *
     * @returns Number of entries cleared
     */
    const clearAllTableCache = (): number => {
        try {
            const keysToRemove: string[] = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && key.startsWith('builder-table-')) {
                    keysToRemove.push(key);
                }
            }
            keysToRemove.forEach(key => localStorage.removeItem(key));
            console.log(`Cleared ${keysToRemove.length} cached table entries`);
            return keysToRemove.length;
        } catch (error) {
            console.warn('Failed to clear cache:', error);
            return 0;
        }
    };

    /**
     * Clear a specific cache entry
     *
     * @param cacheKey - The cache key to remove
     */
    const clearCacheEntry = (cacheKey: string): void => {
        if (!cacheKey) return;

        try {
            localStorage.removeItem(cacheKey);
            console.log(`Cleared cache entry: ${cacheKey}`);
        } catch (error) {
            console.warn('Failed to clear cache entry:', error);
        }
    };

    /**
     * Toggle cache on/off and save preference
     *
     * @param enabled - Whether cache should be enabled
     * @param onToggle - Optional callback to execute after toggle (e.g., refresh data)
     */
    const toggleCache = (enabled: boolean, onToggle?: () => void): void => {
        cacheEnabled.value = enabled;

        // Save preference to localStorage
        try {
            localStorage.setItem('table-cache-enabled', JSON.stringify(enabled));
        } catch (error) {
            console.warn('Failed to save cache preference:', error);
        }

        console.log(`Table cache ${enabled ? 'enabled' : 'disabled'}`);

        // If cache is disabled, clear all cached entries
        if (!enabled) {
            clearAllTableCache();
        }

        // Execute callback if provided
        if (onToggle) {
            onToggle();
        }
    };

    /**
     * Get cache statistics
     *
     * @returns Object with cache statistics
     */
    const getCacheStats = () => {
        let totalEntries = 0;
        let totalSize = 0;

        try {
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && key.startsWith('builder-table-')) {
                    totalEntries++;
                    const item = localStorage.getItem(key);
                    if (item) {
                        totalSize += item.length;
                    }
                }
            }
        } catch (error) {
            console.warn('Failed to get cache stats:', error);
        }

        return {
            totalEntries,
            totalSize,
            totalSizeKB: (totalSize / 1024).toFixed(2),
            enabled: cacheEnabled.value
        };
    };

    return {
        cacheEnabled,
        generateCacheKey,
        getCachedData,
        setCachedData,
        clearAllTableCache,
        clearCacheEntry,
        toggleCache,
        getCacheStats
    };
}
