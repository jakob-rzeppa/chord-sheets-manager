import api, { callApi } from '@/services/api';
import type { ApiResponse, FormatSheetDto } from '@/types/dtos';
import type { AxiosResponse } from 'axios';
import { ref, watch } from 'vue';

export const useSheetFormatter = () => {
    const formatLoading = ref<boolean>(false);
    const formatError = ref<string | null>(null);
    const formatResponse = ref<ApiResponse<FormatSheetDto> | null>(null);

    const formattedSheetContent = ref<string | null>(null);

    watch(formatResponse, (newValue) => {
        if (newValue && newValue.payload) {
            formattedSheetContent.value = newValue.payload.content;
        } else {
            formattedSheetContent.value = null;
        }
    });

    function formatSheet(content: string) {
        callApi<FormatSheetDto>({
            loadingRef: formatLoading,
            errorRef: formatError,
            onSuccess: (response: AxiosResponse<ApiResponse<FormatSheetDto>>) => {
                formatResponse.value = response.data.payload ? { payload: response.data.payload } : null;
            },
            apiCall: () => api.post('v1/sheets/format', { content }),
        });
    }

    return {
        formatLoading,
        formatError,
        formattedSheetContent,
        formatSheet,
    };
};
