import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/routes/two-factor', () => ({
    qrCode: { url: () => '/two-factor/qr-code' },
    secretKey: { url: () => '/two-factor/secret-key' },
    recoveryCodes: { url: () => '/two-factor/recovery-codes' },
}));

import { useTwoFactorAuth } from './useTwoFactorAuth';

function mockFetchOk(body: unknown) {
    global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: async () => body,
    }) as unknown as typeof fetch;
}

function mockFetchFail(status = 500) {
    global.fetch = vi.fn().mockResolvedValue({
        ok: false,
        status,
    }) as unknown as typeof fetch;
}

beforeEach(() => {
    // Reset the module-level singleton state between tests.
    useTwoFactorAuth().clearTwoFactorAuthData();
    vi.restoreAllMocks();
});

describe('fetchQrCode', () => {
    it('stores the svg on success', async () => {
        mockFetchOk({ svg: '<svg/>', url: 'otpauth://x' });
        const tfa = useTwoFactorAuth();
        await tfa.fetchQrCode();
        expect(tfa.qrCodeSvg.value).toBe('<svg/>');
        expect(tfa.errors.value).toHaveLength(0);
    });

    it('records an error and nulls the svg on failure', async () => {
        mockFetchFail();
        const tfa = useTwoFactorAuth();
        await tfa.fetchQrCode();
        expect(tfa.qrCodeSvg.value).toBeNull();
        expect(tfa.errors.value).toContain('Failed to fetch QR code');
    });
});

describe('fetchSetupKey', () => {
    it('stores the manual setup key on success', async () => {
        mockFetchOk({ secretKey: 'ABCD-EFGH' });
        const tfa = useTwoFactorAuth();
        await tfa.fetchSetupKey();
        expect(tfa.manualSetupKey.value).toBe('ABCD-EFGH');
    });

    it('records an error on failure', async () => {
        mockFetchFail();
        const tfa = useTwoFactorAuth();
        await tfa.fetchSetupKey();
        expect(tfa.manualSetupKey.value).toBeNull();
        expect(tfa.errors.value).toContain('Failed to fetch a setup key');
    });
});

describe('fetchRecoveryCodes', () => {
    it('stores the codes on success', async () => {
        mockFetchOk(['code-1', 'code-2']);
        const tfa = useTwoFactorAuth();
        await tfa.fetchRecoveryCodes();
        expect(tfa.recoveryCodesList.value).toEqual(['code-1', 'code-2']);
    });

    it('records an error and empties the list on failure', async () => {
        mockFetchFail();
        const tfa = useTwoFactorAuth();
        await tfa.fetchRecoveryCodes();
        expect(tfa.recoveryCodesList.value).toEqual([]);
        expect(tfa.errors.value).toContain('Failed to fetch recovery codes');
    });
});

describe('fetchSetupData + hasSetupData', () => {
    it('fetches qr + key together and flips hasSetupData true', async () => {
        mockFetchOk({ svg: '<svg/>', url: 'x', secretKey: 'KEY' });
        const tfa = useTwoFactorAuth();
        expect(tfa.hasSetupData.value).toBe(false);

        await tfa.fetchSetupData();
        expect(tfa.qrCodeSvg.value).toBe('<svg/>');
        expect(tfa.manualSetupKey.value).toBe('KEY');
        expect(tfa.hasSetupData.value).toBe(true);
    });
});

describe('clear helpers', () => {
    it('clearSetupData nulls qr + key and clears errors', async () => {
        mockFetchOk({ svg: '<svg/>', url: 'x', secretKey: 'KEY' });
        const tfa = useTwoFactorAuth();
        await tfa.fetchSetupData();

        tfa.clearSetupData();
        expect(tfa.qrCodeSvg.value).toBeNull();
        expect(tfa.manualSetupKey.value).toBeNull();
    });

    it('clearTwoFactorAuthData resets everything', async () => {
        mockFetchOk(['c1']);
        const tfa = useTwoFactorAuth();
        await tfa.fetchRecoveryCodes();
        tfa.errors.value.push('boom');

        tfa.clearTwoFactorAuthData();
        expect(tfa.recoveryCodesList.value).toEqual([]);
        expect(tfa.errors.value).toEqual([]);
        expect(tfa.hasSetupData.value).toBe(false);
    });
});
