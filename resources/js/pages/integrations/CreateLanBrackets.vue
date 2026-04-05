<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { store } from '@/actions/App/Domain/Integration/Http/Controllers/IntegrationAppController';
import { createLanBrackets } from '@/actions/App/Domain/Integration/Http/Controllers/IntegrationAppController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as integrationsRoute } from '@/routes/integrations';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: integrationsRoute().url },
    { title: 'Integrations', href: integrationsRoute().url },
    { title: 'Add LanBrackets', href: createLanBrackets().url },
];
</script>

<template>
    <Head title="Add LanBrackets Integration" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <div>
                <Link
                    :href="integrationsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Integrations
                </Link>
            </div>

            <Form
                v-bind="store.form()"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <!-- Prepopulated hidden fields -->
                <input type="hidden" name="name" value="LanBrackets" />
                <input type="hidden" name="slug" value="lanbrackets" />
                <input
                    type="hidden"
                    name="description"
                    value="Tournament bracket management system"
                />
                <input type="hidden" name="nav_label" value="Brackets" />
                <input type="hidden" name="nav_icon" value="swords" />
                <input
                    type="hidden"
                    name="allowed_scopes[]"
                    value="user:read"
                />
                <input
                    type="hidden"
                    name="allowed_scopes[]"
                    value="user:email"
                />
                <input
                    type="hidden"
                    name="allowed_scopes[]"
                    value="user:roles"
                />
                <input type="hidden" name="send_role_updates" value="1" />

                <!-- Summary -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Add LanBrackets Integration"
                        description="This will create a preconfigured integration for LanBrackets with the recommended settings."
                    />

                    <div
                        class="rounded-lg border border-sidebar-border/70 bg-muted/30 p-4 dark:border-sidebar-border"
                    >
                        <h4 class="mb-3 text-sm font-medium">
                            Preconfigured Settings
                        </h4>
                        <dl class="space-y-2 text-sm">
                            <div class="flex items-center gap-2">
                                <dt class="text-muted-foreground">Name:</dt>
                                <dd class="font-medium">LanBrackets</dd>
                            </div>
                            <div class="flex items-center gap-2">
                                <dt class="text-muted-foreground">Slug:</dt>
                                <dd>
                                    <code
                                        class="rounded bg-muted px-1.5 py-0.5 text-xs"
                                        >lanbrackets</code
                                    >
                                </dd>
                            </div>
                            <div class="flex items-center gap-2">
                                <dt class="text-muted-foreground">
                                    Nav Label:
                                </dt>
                                <dd class="font-medium">Brackets</dd>
                            </div>
                            <div class="flex items-start gap-2">
                                <dt class="text-muted-foreground">Scopes:</dt>
                                <dd class="flex flex-wrap gap-1">
                                    <Badge variant="outline" class="text-xs"
                                        >user:read</Badge
                                    >
                                    <Badge variant="outline" class="text-xs"
                                        >user:email</Badge
                                    >
                                    <Badge variant="outline" class="text-xs"
                                        >user:roles</Badge
                                    >
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Editable fields -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Connection Settings"
                        description="Provide the URLs for your LanBrackets instance"
                    />

                    <div class="grid gap-2">
                        <Label for="callback_url">SSO Callback URL</Label>
                        <Input
                            id="callback_url"
                            name="callback_url"
                            type="url"
                            value="http://localhost:81/auth/callback"
                            placeholder="http://localhost:81/auth/callback"
                        />
                        <p class="text-xs text-muted-foreground">
                            The URL LanCore redirects to after SSO
                            authentication.
                        </p>
                        <InputError :message="errors.callback_url" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="nav_url">Navigation URL</Label>
                        <Input
                            id="nav_url"
                            name="nav_url"
                            type="url"
                            value="http://localhost:81"
                            placeholder="http://localhost:81"
                        />
                        <p class="text-xs text-muted-foreground">
                            The URL users are directed to when clicking the
                            Brackets link in the navigation.
                        </p>
                        <InputError :message="errors.nav_url" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="roles_endpoint">Roles Webhook URL</Label>
                        <Input
                            id="roles_endpoint"
                            name="roles_endpoint"
                            type="url"
                            value="http://localhost:81/api/webhooks/roles"
                            placeholder="http://localhost:81/api/webhooks/roles"
                        />
                        <p class="text-xs text-muted-foreground">
                            The endpoint LanCore will call when user roles change.
                        </p>
                        <InputError :message="errors.roles_endpoint" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            name="is_active"
                            :default-value="true"
                        />
                        <Label for="is_active">Active</Label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{
                            processing
                                ? 'Creating...'
                                : 'Create LanBrackets Integration'
                        }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
