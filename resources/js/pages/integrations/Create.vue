<script setup lang="ts">
import { store } from '@/actions/App/Domain/Integration/Http/Controllers/IntegrationAppController'
import { create as integrationCreate } from '@/actions/App/Domain/Integration/Http/Controllers/IntegrationAppController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as integrationsRoute } from '@/routes/integrations'
import type { BreadcrumbItem } from '@/types'
import { Form, Head, Link } from '@inertiajs/vue3'

defineProps<{
    availableScopes: { value: string; label: string; description: string }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: integrationsRoute().url },
    { title: 'Integrations', href: integrationsRoute().url },
    { title: 'Create', href: integrationCreate().url },
]
</script>

<template>
    <Head title="Create Integration" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
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
                <!-- Basic Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Integration Details"
                        description="Configure the integration application"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            placeholder="e.g. LanShout"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="slug">Slug</Label>
                        <Input
                            id="slug"
                            name="slug"
                            required
                            placeholder="e.g. lanshout"
                        />
                        <p class="text-xs text-muted-foreground">Unique identifier for this integration. Use lowercase letters, numbers, and dashes.</p>
                        <InputError :message="errors.slug" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            rows="3"
                            placeholder="What does this integration do?"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="callback_url">Callback URL</Label>
                        <Input
                            id="callback_url"
                            name="callback_url"
                            type="url"
                            placeholder="https://lanshout.example.com/callback"
                        />
                        <p class="text-xs text-muted-foreground">Optional URL for bootstrap redirects.</p>
                        <InputError :message="errors.callback_url" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox id="is_active" name="is_active" :default-value="true" />
                        <Label for="is_active">Active</Label>
                    </div>
                </div>

                <!-- Navigation Link -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Navigation Link"
                        description="Show a shortcut button in the top navigation bar for logged-in users"
                    />

                    <div class="grid gap-2">
                        <Label for="nav_url">Link URL</Label>
                        <Input
                            id="nav_url"
                            name="nav_url"
                            type="url"
                            placeholder="https://lanshout.example.com"
                        />
                        <p class="text-xs text-muted-foreground">The URL users are directed to when clicking the button.</p>
                        <InputError :message="errors.nav_url" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="nav_label">Button Label</Label>
                        <Input
                            id="nav_label"
                            name="nav_label"
                            placeholder="e.g. LanShout"
                        />
                        <InputError :message="errors.nav_label" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="nav_icon">Icon Name</Label>
                        <Input
                            id="nav_icon"
                            name="nav_icon"
                            placeholder="e.g. megaphone, message-circle, radio"
                        />
                        <p class="text-xs text-muted-foreground">
                            A <a href="https://lucide.dev/icons" target="_blank" rel="noopener" class="underline hover:text-foreground">Lucide icon</a> name (lowercase, kebab-case). Leave empty for a default link icon.
                        </p>
                        <InputError :message="errors.nav_icon" />
                    </div>
                </div>

                <!-- Scopes -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Allowed Scopes"
                        description="Select which data this integration can access"
                    />

                    <div class="space-y-3">
                        <div v-for="scope in availableScopes" :key="scope.value" class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                :id="`scope-${scope.value}`"
                                name="allowed_scopes[]"
                                :value="scope.value"
                                class="mt-0.5 size-4 shrink-0 rounded-[4px] border border-input accent-primary"
                            />
                            <div class="grid gap-0.5">
                                <Label :for="`scope-${scope.value}`" class="cursor-pointer">{{ scope.label }}</Label>
                                <p class="text-xs text-muted-foreground">{{ scope.description }}</p>
                            </div>
                        </div>
                    </div>
                    <InputError :message="errors['allowed_scopes']" />
                </div>

                <!-- Announcements -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Announcements"
                        description="Forward published announcements to this integration via a managed webhook"
                    />

                    <div class="flex items-center gap-2">
                        <Checkbox id="send_announcements" name="send_announcements" />
                        <Label for="send_announcements">Send Announcements</Label>
                    </div>

                    <div class="grid gap-2">
                        <Label for="announcement_endpoint">Announcement Endpoint</Label>
                        <Input
                            id="announcement_endpoint"
                            name="announcement_endpoint"
                            type="url"
                            placeholder="https://lanshout.example.com/api/announcements"
                        />
                        <p class="text-xs text-muted-foreground">The URL that will receive announcement payloads via a managed webhook.</p>
                        <InputError :message="errors.announcement_endpoint" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="announcement_webhook_secret">Webhook Secret</Label>
                        <Input
                            id="announcement_webhook_secret"
                            name="announcement_webhook_secret"
                            type="password"
                            placeholder="Optional secret for HMAC-SHA256 signature verification"
                            autocomplete="off"
                        />
                        <p class="text-xs text-muted-foreground">
                            Used to sign announcement payloads via HMAC-SHA256. Must match the <code>LANCORE_WEBHOOK_SECRET</code> set in the integration.
                        </p>
                        <InputError :message="errors.announcement_webhook_secret" />
                    </div>
                </div>

                <!-- Role Updates -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Role Updates"
                        description="Notify this integration when a user's roles change via a managed webhook"
                    />

                    <div class="flex items-center gap-2">
                        <Checkbox id="send_role_updates" name="send_role_updates" />
                        <Label for="send_role_updates">Send Role Updates</Label>
                    </div>

                    <div class="grid gap-2">
                        <Label for="roles_endpoint">Roles Endpoint</Label>
                        <Input
                            id="roles_endpoint"
                            name="roles_endpoint"
                            type="url"
                            placeholder="https://lanshout.example.com/api/webhooks/roles"
                        />
                        <p class="text-xs text-muted-foreground">The URL that will receive role update payloads via a managed webhook.</p>
                        <InputError :message="errors.roles_endpoint" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="roles_webhook_secret">Webhook Secret</Label>
                        <Input
                            id="roles_webhook_secret"
                            name="roles_webhook_secret"
                            type="password"
                            placeholder="Optional secret for HMAC-SHA256 signature verification"
                            autocomplete="off"
                        />
                        <p class="text-xs text-muted-foreground">
                            Used to sign role update payloads via HMAC-SHA256. Must match the secret configured in the integration.
                        </p>
                        <InputError :message="errors.roles_webhook_secret" />
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button
                        type="submit"
                        :disabled="processing"
                    >
                        {{ processing ? 'Creating…' : 'Create Integration' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
