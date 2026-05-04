import { computed, ref } from 'vue';

type TestStatus =
    | 'connected'
    | 'auth_failed'
    | 'not_configured'
    | 'unreachable';

type TestResponse = {
    status: TestStatus | string;
    account?: string;
    error?: string;
};

/**
 * Hook for the External API page connectivity test buttons. Each card on
 * `orchestration/apis/Index.vue` instantiates this with its corresponding
 * `external-apis/test-*` endpoint URL — the resulting state, classes, and
 * label keep every card visually consistent.
 */
export function useExternalApiTest(
    getUrl: () => string,
    onSuccess?: () => void,
) {
    const testing = ref(false);
    const result = ref<'idle' | 'success' | 'failure'>('idle');
    const error = ref('');
    const statusLabel = ref('');
    const accountName = ref('');

    async function run() {
        testing.value = true;
        result.value = 'idle';
        error.value = '';
        statusLabel.value = '';
        accountName.value = '';

        try {
            const csrfToken =
                document.querySelector<HTMLMetaElement>(
                    'meta[name="csrf-token"]',
                )?.content ?? '';

            const response = await fetch(getUrl(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            const data = (await response.json()) as TestResponse;

            if (data.status === 'connected') {
                result.value = 'success';
                statusLabel.value = 'Connected!';
                accountName.value = data.account ?? '';
                onSuccess?.();
            } else {
                result.value = 'failure';
                statusLabel.value =
                    data.status === 'auth_failed'
                        ? 'Auth Failed'
                        : data.status === 'not_configured'
                          ? 'Not Configured'
                          : 'Unreachable';
                error.value = data.error ?? '';
            }
        } catch {
            result.value = 'failure';
            statusLabel.value = 'Request Failed';
            error.value = 'Could not reach the server.';
        } finally {
            testing.value = false;
        }
    }

    const buttonClass = computed(() => {
        if (result.value === 'success') {
            return 'border-green-500 bg-green-50 text-green-700 hover:bg-green-100 dark:border-green-600 dark:bg-green-950 dark:text-green-400 dark:hover:bg-green-900';
        }

        if (result.value === 'failure') {
            return 'border-red-500 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-600 dark:bg-red-950 dark:text-red-400 dark:hover:bg-red-900';
        }

        return '';
    });

    function buttonLabel(idle = 'Test Connection'): string {
        if (testing.value) {
            return 'Testing...';
        }

        if (result.value === 'success') {
            return 'Connected!';
        }

        if (result.value === 'failure') {
            return statusLabel.value || 'Failed';
        }

        return idle;
    }

    return {
        testing,
        result,
        error,
        statusLabel,
        accountName,
        run,
        buttonClass,
        buttonLabel,
    };
}
