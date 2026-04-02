<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    store as storePushSubscription,
    destroy as destroyPushSubscription,
} from '@/actions/App/Domain/Notification/Http/Controllers/PushSubscriptionController';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    /** When true, renders as a settings control (no dismiss). When false, renders as a prompt banner. */
    settingsMode?: boolean;
}>();

const page = usePage();
const dismissed = ref(false);
const loading = ref(false);
const subscribed = ref(page.props.pushSubscribed as boolean);
const permissionState = ref<NotificationPermission>(
    typeof Notification !== 'undefined' ? Notification.permission : 'default',
);

const isPushSupported =
    typeof window !== 'undefined' &&
    'serviceWorker' in navigator &&
    'PushManager' in window &&
    'Notification' in window;

const vapidPublicKey = page.props.vapidPublicKey as string;

function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    const rawData = window.atob(base64);

    return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

async function subscribe(): Promise<void> {
    if (!isPushSupported || !vapidPublicKey) {
        return;
    }

    loading.value = true;

    try {
        const registration = await navigator.serviceWorker.register('/sw.js');
        await navigator.serviceWorker.ready;

        const existing = await registration.pushManager.getSubscription();

        if (existing) {
            await existing.unsubscribe();
        }

        const pushSubscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
        });

        const json = pushSubscription.toJSON();
        const p256dh = json.keys?.p256dh ?? '';
        const auth = json.keys?.auth ?? '';

        await fetch(storePushSubscription().url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement | null
                    )?.content ?? '',
            },
            body: JSON.stringify({
                endpoint: pushSubscription.endpoint,
                public_key: p256dh,
                auth_token: auth,
                content_encoding: 'aesgcm',
            }),
        });

        subscribed.value = true;
        permissionState.value = 'granted';
        dismissed.value = true;

        // Update shared prop so other components reflect the change without a full reload
        page.props.pushSubscribed = true;
    } catch {
        permissionState.value = Notification.permission;
    } finally {
        loading.value = false;
    }
}

async function unsubscribe(): Promise<void> {
    if (!isPushSupported) {
        return;
    }

    loading.value = true;

    try {
        const registration =
            await navigator.serviceWorker.getRegistration('/sw.js');
        const pushSubscription = registration
            ? await registration.pushManager.getSubscription()
            : null;

        if (pushSubscription) {
            await fetch(destroyPushSubscription().url, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        (
                            document.querySelector(
                                'meta[name="csrf-token"]',
                            ) as HTMLMetaElement | null
                        )?.content ?? '',
                },
                body: JSON.stringify({ endpoint: pushSubscription.endpoint }),
            });

            await pushSubscription.unsubscribe();
        }

        subscribed.value = false;
        page.props.pushSubscribed = false;
    } finally {
        loading.value = false;
    }
}

function dismiss(): void {
    dismissed.value = true;
}

// Show the prompt when: not in settings mode, push is supported, user hasn't subscribed yet,
// browser permission isn't denied, and user hasn't dismissed this session.
const shouldShowPrompt =
    !props.settingsMode &&
    isPushSupported &&
    !subscribed.value &&
    permissionState.value !== 'denied';
</script>

<template>
    <!-- Settings mode: inline control -->
    <template v-if="settingsMode && isPushSupported">
        <div class="flex items-center justify-between rounded-lg border p-4">
            <div class="flex items-start gap-3">
                <Bell class="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                <div>
                    <p class="text-sm font-medium">
                        Browser push notifications
                    </p>
                    <p class="text-xs text-muted-foreground">
                        <template v-if="permissionState === 'denied'">
                            Push notifications are blocked in your browser.
                            Enable them in your browser settings.
                        </template>
                        <template v-else-if="subscribed">
                            You will receive push notifications in this browser.
                        </template>
                        <template v-else>
                            Get notified instantly in this browser, even when
                            the site is not open.
                        </template>
                    </p>
                </div>
            </div>
            <div class="ml-4 shrink-0">
                <Button
                    v-if="!subscribed && permissionState !== 'denied'"
                    size="sm"
                    :disabled="loading"
                    @click="subscribe"
                >
                    {{ loading ? 'Enabling…' : 'Enable' }}
                </Button>
                <Button
                    v-else-if="subscribed"
                    variant="outline"
                    size="sm"
                    :disabled="loading"
                    @click="unsubscribe"
                >
                    {{ loading ? 'Disabling…' : 'Disable' }}
                </Button>
                <span v-else class="text-xs text-muted-foreground"
                    >Blocked</span
                >
            </div>
        </div>
    </template>

    <!-- Prompt banner: shown once after login -->
    <template v-else-if="shouldShowPrompt && !dismissed">
        <div
            class="fixed right-4 bottom-4 z-50 w-80 rounded-xl border bg-card p-4 shadow-lg"
        >
            <div class="flex items-start gap-3">
                <div class="rounded-full bg-primary/10 p-2 text-primary">
                    <Bell class="size-4" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold">
                        Enable push notifications
                    </p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Get instant notifications in your browser, even when the
                        site isn't open.
                    </p>
                    <div class="mt-3 flex gap-2">
                        <Button
                            size="sm"
                            :disabled="loading"
                            @click="subscribe"
                        >
                            {{ loading ? 'Enabling…' : 'Enable' }}
                        </Button>
                        <Button variant="ghost" size="sm" @click="dismiss"
                            >Not now</Button
                        >
                    </div>
                </div>
            </div>
        </div>
    </template>
</template>
