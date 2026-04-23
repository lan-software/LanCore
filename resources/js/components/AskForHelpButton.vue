<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { LifeBuoy } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

type Props = {
    subject: string;
    category?: string;
    orderReference?: string;
    extraLinks?: string[];
};

const props = withDefaults(defineProps<Props>(), {
    category: undefined,
    orderReference: undefined,
    extraLinks: () => [],
});

const { t } = useI18n();
const page = usePage();

const helpEntry = computed(() => {
    const links = page.props.integrationLinks ?? [];

    return links.find((link) => link.icon === 'life-buoy') ?? null;
});

const helpUrl = computed<string | null>(() => {
    const entry = helpEntry.value;

    if (!entry) {
        return null;
    }

    let base: URL;

    try {
        base = new URL('/tickets/create', entry.url);
    } catch {
        return null;
    }

    const params = new URLSearchParams();
    params.set('subject', props.subject);

    if (props.category) {
        params.set('category', props.category);
    }

    params.set('context[source_product]', 'lancore');

    if (typeof window !== 'undefined') {
        params.set('context[source_domain]', window.location.origin);
    }

    if (props.orderReference) {
        params.set('context[order_reference]', props.orderReference);
    }

    const links =
        props.extraLinks.length > 0
            ? props.extraLinks
            : typeof window !== 'undefined'
              ? [window.location.href]
              : [];

    for (const link of links) {
        params.append('context[links][]', link);
    }

    base.search = params.toString();

    return base.toString();
});
</script>

<template>
    <TooltipProvider v-if="helpUrl">
        <Tooltip>
            <TooltipTrigger as-child>
                <a
                    :href="helpUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    :aria-label="t('help.askForHelp')"
                    data-testid="ask-for-help-link"
                    class="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                >
                    <LifeBuoy class="size-5" />
                </a>
            </TooltipTrigger>
            <TooltipContent side="bottom">
                {{ t('help.askForHelpTooltip') }}
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
