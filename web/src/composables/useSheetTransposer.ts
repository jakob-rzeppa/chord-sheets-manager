import api, { callApi } from '@/services/api';
import type { ApiResponse, TransposeSheetDto } from '@/types/dtos';
import { ref, watch } from 'vue';

export const useSheetTransposer = () => {
    const transposeLoading = ref<boolean>(false);
    const transposeError = ref<string | null>(null);
    const transposeResponse = ref<ApiResponse<TransposeSheetDto> | null>(null);

    const transposedSheetContent = ref<string | null>(null);

    watch(transposeResponse, (newValue) => {
        if (newValue && newValue.payload) {
            transposedSheetContent.value = newValue.payload.content;
        } else {
            transposedSheetContent.value = null;
        }
    });

    const transposeSheet = (content: string, transposeDir: 'up' | 'down') => {
        callApi<TransposeSheetDto>({
            loadingRef: transposeLoading,
            errorRef: transposeError,
            onSuccess: (response) => {
                transposeResponse.value = response.data;
            },
            apiCall: () => api.post('v1/sheets/transpose', { content, dir: transposeDir }),
        }).then(() => {
            if (transposeError.value) {
                return;
            }

            if (!transposeResponse.value || !transposeResponse.value.payload) {
                transposeError.value = 'API Transpose Response not found.';
                return;
            }
        });
    };

    return {
        transposeLoading,
        transposeError,
        transposedSheetContent,
        transposeSheet,
    };
};
