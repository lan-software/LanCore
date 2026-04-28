<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { showCookiePreferences } from '@/lib/cookieConsent';
import { impressum, index as legalIndex } from '@/routes/legal';

type AppVersion = {
    version: string;
    commit: string | null;
    builtAt: string | null;
    phpVersion: string;
    laravelVersion: string;
};

withDefaults(
    defineProps<{
        detailed?: boolean;
    }>(),
    {
        detailed: false,
    },
);

const page = usePage();

const appVersion = computed<AppVersion | undefined>(
    () => (page.props.appVersion as AppVersion | undefined) ?? undefined,
);

const versionLabel = computed(() => {
    const v = appVersion.value;

    if (!v) {
        return '';
    }

    return v.commit ? `v${v.version} (${v.commit})` : `v${v.version}`;
});
</script>

<template>
    <footer class="border-t">
        <div
            class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-6 py-4 text-xs text-muted-foreground"
        >
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                <span> Powered by LanCore {{ new Date().getFullYear() }} </span>
                <span v-if="appVersion" class="font-mono">{{
                    versionLabel
                }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                <Link :href="impressum().url" class="hover:text-foreground">
                    {{ $t('legal.impressum.link') }}
                </Link>
                <Link :href="legalIndex().url" class="hover:text-foreground">
                    {{ $t('legal.index.link') }}
                </Link>
                <button
                    type="button"
                    class="hover:text-foreground"
                    @click="showCookiePreferences"
                >
                    {{ $t('footer.cookieSettings') }}
                </button>
            </div>
        </div>

        <div
            v-if="detailed && appVersion"
            class="mx-auto max-w-5xl border-t px-6 py-3 font-mono text-xs text-muted-foreground"
        >
            <dl class="grid grid-cols-2 gap-x-4 gap-y-1 sm:grid-cols-4">
                <div>
                    <dt class="inline font-sans">
                        {{ $t('footer.version') }}:
                    </dt>
                    <dd class="inline">{{ appVersion.version }}</dd>
                </div>
                <div v-if="appVersion.commit">
                    <dt class="inline font-sans">{{ $t('footer.commit') }}:</dt>
                    <dd class="inline">{{ appVersion.commit }}</dd>
                </div>
                <div v-if="appVersion.builtAt">
                    <dt class="inline font-sans">
                        {{ $t('footer.builtAt') }}:
                    </dt>
                    <dd class="inline">{{ appVersion.builtAt }}</dd>
                </div>
                <div>
                    <dt class="inline font-sans">PHP:</dt>
                    <dd class="inline">{{ appVersion.phpVersion }}</dd>
                </div>
                <div>
                    <dt class="inline font-sans">Laravel:</dt>
                    <dd class="inline">{{ appVersion.laravelVersion }}</dd>
                </div>
            </dl>
        </div>
    </footer>
</template>
