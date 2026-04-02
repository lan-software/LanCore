<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { House, ExternalLink } from 'lucide-vue-next';
import { computed, defineAsyncComponent  } from 'vue';
import type {Component} from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { home } from '@/routes';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const integrationLinks = computed(() => page.props.integrationLinks ?? []);

const iconCache = new Map<string, Component>();

function resolveIcon(name: string | null): Component {
    if (!name) {
return ExternalLink;
}

    if (iconCache.has(name)) {
return iconCache.get(name)!;
}

    const pascalCase = name
        .split('-')
        .map((s) => s.charAt(0).toUpperCase() + s.slice(1))
        .join('');

    const asyncIcon = defineAsyncComponent({
        loader: () =>
            import('lucide-vue-next').then((mod) => {
                const icon = (mod as Record<string, Component>)[pascalCase];

                return icon ?? ExternalLink;
            }),
        loadingComponent: ExternalLink,
    });

    iconCache.set(name, asyncIcon);

    return asyncIcon;
}
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex items-center gap-1">
            <TooltipProvider v-if="integrationLinks.length > 0">
                <Tooltip v-for="link in integrationLinks" :key="link.url">
                    <TooltipTrigger as-child>
                        <a
                            :href="link.url"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        >
                            <component :is="resolveIcon(link.icon)" class="size-4" />
                        </a>
                    </TooltipTrigger>
                    <TooltipContent>
                        {{ link.label }}
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
            <Link
                :href="home()"
                class="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                title="Go to homepage"
            >
                <House class="size-4" />
            </Link>
            <NotificationBell />
        </div>
    </header>
</template>
