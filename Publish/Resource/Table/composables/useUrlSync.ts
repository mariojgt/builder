/**
 * Composable for syncing table filters with URL query parameters
 * Allows sharing filtered table states via URL
 */

export function useUrlSync() {
    /**
     * Encode filters to URL query parameters
     */
    const encodeFiltersToUrl = (
        perPage: number,
        filterBy: string,
        orderBy: string,
        search: string | null,
        activeFilters: Record<string, any>,
        currentAdvancedFilters: any[]
    ): void => {
        if (typeof window === 'undefined') return;

        const params = new URLSearchParams();

        // Add basic parameters
        if (perPage && perPage !== 10) {
            params.set('perPage', perPage.toString());
        }
        if (filterBy) {
            params.set('filterBy', filterBy);
        }
        if (orderBy && orderBy !== 'desc') {
            params.set('orderBy', orderBy);
        }
        if (search) {
            params.set('search', search);
        }

        // Encode active filters as plain JSON (URL-encoded by URLSearchParams)
        // This makes URLs readable: ?filters={"is_active":{"value":"true"}}
        if (activeFilters && Object.keys(activeFilters).length > 0) {
            params.set('filters', JSON.stringify(activeFilters));
        }

        // Encode advanced filters configuration as plain JSON
        if (currentAdvancedFilters && currentAdvancedFilters.length > 0) {
            const enabledFilters = currentAdvancedFilters.filter(f => f.enabled !== false);
            if (enabledFilters.length > 0) {
                params.set('advFilters', JSON.stringify(enabledFilters));
            }
        }

        // Update URL without reloading the page
        const newUrl = params.toString()
            ? `${window.location.pathname}?${params.toString()}`
            : window.location.pathname;

        window.history.pushState({}, '', newUrl);
        console.log('✅ URL updated with filters:', newUrl);
    };

    /**
     * Decode filters from URL query parameters
     */
    const decodeFiltersFromUrl = (): {
        perPage: number | null;
        filterBy: string | null;
        orderBy: string | null;
        search: string | null;
        activeFilters: Record<string, any> | null;
        currentAdvancedFilters: any[] | null;
    } => {
        if (typeof window === 'undefined') {
            return {
                perPage: null,
                filterBy: null,
                orderBy: null,
                search: null,
                activeFilters: null,
                currentAdvancedFilters: null
            };
        }

        console.log('🔍 Current window.location.search:', window.location.search);
        console.log('🔍 Current window.location.href:', window.location.href);

        const params = new URLSearchParams(window.location.search);
        
        console.log('🔍 All URL params keys:', Array.from(params.keys()));
        console.log('🔍 All URL params entries:', Array.from(params.entries()));

        let activeFilters = null;
        let currentAdvancedFilters = null;

        try {
            // Decode active filters from plain JSON
            // URLSearchParams.get() automatically URL-decodes the value
            const filtersParam = params.get('filters');
            console.log('🔍 Raw filters param from URL (type:', typeof filtersParam, '):', filtersParam);
            
            if (filtersParam) {
                activeFilters = JSON.parse(filtersParam);
                console.log('📥 Decoded activeFilters from URL:', activeFilters);
            } else {
                console.warn('⚠️ No filters parameter found in URL');
            }

            // Decode advanced filters from plain JSON
            const advFiltersParam = params.get('advFilters');
            if (advFiltersParam) {
                console.log('🔍 Raw advFilters param from URL:', advFiltersParam);
                currentAdvancedFilters = JSON.parse(advFiltersParam);
                console.log('📥 Decoded advancedFilters from URL:', currentAdvancedFilters);
            }
        } catch (e) {
            console.error('⚠️ Failed to decode filters from URL:', e);
            console.error('URL search params:', window.location.search);
        }

        const result = {
            perPage: params.get('perPage') ? parseInt(params.get('perPage')!) : null,
            filterBy: params.get('filterBy'),
            orderBy: params.get('orderBy'),
            search: params.get('search'),
            activeFilters,
            currentAdvancedFilters
        };

        console.log('📥 All decoded URL params:', result);
        return result;
    };

    /**
     * Clear URL parameters
     */
    const clearUrlParams = (): void => {
        if (typeof window === 'undefined') return;
        window.history.pushState({}, '', window.location.pathname);
    };

    /**
     * Check if URL has filter parameters
     */
    const hasUrlParams = (): boolean => {
        if (typeof window === 'undefined') return false;
        return window.location.search.length > 0;
    };

    return {
        encodeFiltersToUrl,
        decodeFiltersFromUrl,
        clearUrlParams,
        hasUrlParams
    };
}
