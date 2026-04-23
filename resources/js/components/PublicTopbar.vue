<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Calendar,
    ChevronDown,
    Clock,
    ExternalLink,
    LayoutGrid,
    Menu,
    ShoppingCart,
    X,
} from 'lucide-vue-next';
import { computed, defineAsyncComponent, ref } from 'vue';
import type { Component } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavUser from '@/components/NavUser.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { dashboard, login, register } from '@/routes';
import { index as shopIndex } from '@/routes/shop';

withDefaults(defineProps<{ canRegister?: boolean }>(), { canRegister: true });

const page = usePage();
const integrationLinks = computed(() => page.props.integrationLinks ?? []);
const mobileMenuOpen = ref(false);

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
    <header class="border-b">
        <Collapsible v-model:open="mobileMenuOpen" class="md:hidden">
            <div
                class="mx-auto flex h-14 max-w-5xl items-center justify-between px-4 sm:px-6"
            >
                <Link href="/" class="flex items-center">
                    <AppLogo />
                </Link>
                <div class="flex items-center gap-2">
                    <NotificationBell v-if="$page.props.auth.user" />
                    <NavUser v-if="$page.props.auth.user" variant="topbar" />
                    <CollapsibleTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex size-9 items-center justify-center rounded-full border border-border/70 bg-background/80 text-muted-foreground shadow-sm transition hover:bg-accent hover:text-accent-foreground"
                            aria-label="Toggle navigation menu"
                        >
                            <Menu v-if="!mobileMenuOpen" class="size-4" />
                            <X v-else class="size-4" />
                        </button>
                    </CollapsibleTrigger>
                </div>
            </div>

            <CollapsibleContent class="border-t">
                <nav
                    class="mx-auto flex max-w-5xl flex-col gap-3 px-4 py-4 sm:px-6"
                >
                    <Link
                        href="/upcoming-events"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-muted-foreground transition hover:bg-accent hover:text-foreground"
                    >
                        <Calendar class="size-4" />
                        Upcoming Events
                    </Link>
                    <Link
                        href="/past-events"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-muted-foreground transition hover:bg-accent hover:text-foreground"
                    >
                        <Clock class="size-4" />
                        Past Events
                    </Link>
                    <Link
                        :href="shopIndex().url"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-muted-foreground transition hover:bg-accent hover:text-foreground"
                    >
                        <ShoppingCart class="size-4" />
                        Shop
                    </Link>
                    <a
                        v-for="link in integrationLinks"
                        :key="link.url"
                        :href="link.url"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-muted-foreground transition hover:bg-accent hover:text-foreground"
                    >
                        <component
                            :is="resolveIcon(link.icon)"
                            class="size-4"
                        />
                        {{ link.label }}
                    </a>

                    <template v-if="$page.props.auth.user">
                        <div
                            class="flex items-center justify-between rounded-lg border border-border/70 px-3 py-2"
                        >
                            <span class="text-sm font-medium text-foreground"
                                >Notifications</span
                            >
                            <NotificationBell />
                        </div>
                        <Link
                            :href="dashboard()"
                            class="rounded-lg bg-primary px-4 py-2.5 text-center text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-lg px-3 py-2 text-sm text-muted-foreground transition hover:bg-accent hover:text-foreground"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-lg bg-primary px-4 py-2.5 text-center text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Register
                        </Link>
                    </template>
                </nav>
            </CollapsibleContent>
        </Collapsible>

        <div
            class="mx-auto hidden max-w-5xl items-center justify-between px-6 py-4 md:flex"
        >
            <Link href="/" class="flex items-center">
                <AppLogo />
            </Link>
            <nav class="flex items-center gap-4">
                <Link
                    href="/upcoming-events"
                    class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                >
                    <Calendar class="size-4" />
                    Upcoming Events
                </Link>
                <Link
                    href="/past-events"
                    class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                >
                    <Clock class="size-4" />
                    Past Events
                </Link>
                <Link
                    :href="shopIndex().url"
                    class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ShoppingCart class="size-4" />
                    Shop
                </Link>
                <DropdownMenu v-if="integrationLinks.length > 0">
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                            data-test="topbar-apps-button"
                        >
                            <LayoutGrid class="size-4" />
                            Apps
                            <ChevronDown class="size-3.5 opacity-70" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        class="min-w-56 rounded-lg"
                        align="end"
                        :side-offset="8"
                    >
                        <DropdownMenuLabel>Other apps</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            v-for="link in integrationLinks"
                            :key="link.url"
                            as-child
                        >
                            <a
                                :href="link.url"
                                target="_blank"
                                rel="noopener"
                                class="flex items-center gap-2"
                            >
                                <component
                                    :is="resolveIcon(link.icon)"
                                    class="size-4"
                                />
                                {{ link.label }}
                            </a>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
                <NotificationBell v-if="$page.props.auth.user" />
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                >
                    Dashboard
                </Link>
                <NavUser v-if="$page.props.auth.user" variant="topbar" />
                <template v-else>
                    <Link
                        :href="login()"
                        class="text-sm text-muted-foreground hover:text-foreground"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </div>
    </header>
</template>
