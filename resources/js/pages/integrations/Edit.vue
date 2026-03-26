<script setup lang="ts">
import { update, destroy } from '@/actions/App/Domain/Integration/Http/Controllers/IntegrationAppController'
import { store as storeToken, destroy as destroyToken, rotate as rotateToken } from '@/actions/App/Domain/Integration/Http/Controllers/IntegrationTokenController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as integrationsRoute } from '@/routes/integrations'
import type { BreadcrumbItem } from '@/types'
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3'
import { Copy, Key, RefreshCw, Trash2 } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type IntegrationToken = {
    id: number
    name: string
    plain_text_prefix: string
    last_used_at: string | null
    expires_at: string | null
    revoked_at: string | null
    created_at: string
}

type IntegrationApp = {
    id: number
    name: string
    slug: string
    description: string | null
    callback_url: string | null
    nav_url: string | null
    nav_icon: string | null
    nav_label: string | null
    allowed_scopes: string[] | null
    is_active: boolean
    tokens: IntegrationToken[]
    created_at: string
    updated_at: string
}

const props = defineProps<{
    integrationApp: IntegrationApp
    availableScopes: { value: string; label: string; description: string }[]
}>()

const page = usePage()

const newToken = computed(() => page.props.flash?.newToken as string | undefined)
const tokenCopied = ref(false)
const showDeleteDialog = ref(false)
const showRevokeDialog = ref(false)
const revokeTokenId = ref<number | null>(null)
const showRotateDialog = ref(false)
const rotateTokenId = ref<number | null>(null)

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: integrationsRoute().url },
    { title: 'Integrations', href: integrationsRoute().url },
    { title: props.integrationApp.name, href: '#' },
]

function copyToken() {
    if (newToken.value) {
        navigator.clipboard.writeText(newToken.value)
        tokenCopied.value = true
        setTimeout(() => (tokenCopied.value = false), 2000)
    }
}

function deleteApp() {
    router.delete(destroy(props.integrationApp.id).url, {
        onSuccess: () => (showDeleteDialog.value = false),
    })
}

function revokeToken(tokenId: number) {
    router.delete(destroyToken({ integration: props.integrationApp.id, token: tokenId }).url, {
        onSuccess: () => {
            showRevokeDialog.value = false
            revokeTokenId.value = null
        },
    })
}

function confirmRevoke(tokenId: number) {
    revokeTokenId.value = tokenId
    showRevokeDialog.value = true
}

function confirmRotate(tokenId: number) {
    rotateTokenId.value = tokenId
    showRotateDialog.value = true
}

function rotateTokenAction(tokenId: number) {
    router.post(rotateToken({ integration: props.integrationApp.id, token: tokenId }).url, {}, {
        onSuccess: () => {
            showRotateDialog.value = false
            rotateTokenId.value = null
        },
    })
}

function isTokenActive(token: IntegrationToken): boolean {
    if (token.revoked_at) return false
    if (token.expires_at && new Date(token.expires_at) < new Date()) return false
    return true
}
</script>

<template>
    <Head :title="`Edit ${integrationApp.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-3xl">
            <div>
                <Link
                    :href="integrationsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Integrations
                </Link>
            </div>

            <!-- New Token Alert -->
            <div v-if="newToken" class="rounded-md border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <Key class="size-4 text-green-600 dark:text-green-400" />
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">Token created successfully</p>
                </div>
                <p class="text-xs text-green-700 dark:text-green-300 mb-2">
                    Copy this token now. It will not be shown again.
                </p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 text-xs bg-white dark:bg-green-900 border rounded px-3 py-2 font-mono break-all select-all">{{ newToken }}</code>
                    <Button variant="outline" size="sm" @click="copyToken">
                        <Copy class="size-4" />
                        {{ tokenCopied ? 'Copied!' : 'Copy' }}
                    </Button>
                </div>
            </div>

            <!-- Edit Form -->
            <Form
                v-bind="update.form(integrationApp.id)"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Integration Details"
                        description="Update the integration application"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            :default-value="integrationApp.name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Slug</Label>
                        <code class="text-sm bg-muted px-3 py-2 rounded">{{ integrationApp.slug }}</code>
                        <p class="text-xs text-muted-foreground">Slugs cannot be changed after creation.</p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            rows="3"
                            :default-value="integrationApp.description ?? ''"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="callback_url">Callback URL</Label>
                        <Input
                            id="callback_url"
                            name="callback_url"
                            type="url"
                            :default-value="integrationApp.callback_url ?? ''"
                        />
                        <InputError :message="errors.callback_url" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox id="is_active" name="is_active" :default-value="integrationApp.is_active" />
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
                            :default-value="integrationApp.nav_url ?? ''"
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
                            :default-value="integrationApp.nav_label ?? ''"
                            placeholder="e.g. LanShout"
                        />
                        <InputError :message="errors.nav_label" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="nav_icon">Icon Name</Label>
                        <Input
                            id="nav_icon"
                            name="nav_icon"
                            :default-value="integrationApp.nav_icon ?? ''"
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
                                :checked="integrationApp.allowed_scopes?.includes(scope.value)"
                                class="mt-0.5 size-4 shrink-0 rounded-[4px] border border-input accent-primary"
                            />
                            <div class="grid gap-0.5">
                                <Label :for="`scope-${scope.value}`" class="cursor-pointer">{{ scope.label }}</Label>
                                <p class="text-xs text-muted-foreground">{{ scope.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving…' : 'Save Changes' }}
                    </Button>
                </div>
            </Form>

            <!-- API Tokens Section -->
            <div class="space-y-4 border-t pt-8">
                <Heading
                    variant="small"
                    title="API Tokens"
                    description="Tokens used by this integration to authenticate API requests"
                />

                <!-- Create Token Form -->
                <Form
                    v-bind="storeToken.form(integrationApp.id)"
                    class="flex items-end gap-4"
                    v-slot="{ errors: tokenErrors, processing: tokenProcessing }"
                >
                    <div class="grid gap-2 flex-1">
                        <Label for="token_name">Token Name</Label>
                        <Input
                            id="token_name"
                            name="name"
                            required
                            placeholder="e.g. Production"
                        />
                        <InputError :message="tokenErrors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="token_expires_at">Expires At</Label>
                        <Input
                            id="token_expires_at"
                            name="expires_at"
                            type="date"
                        />
                        <InputError :message="tokenErrors.expires_at" />
                    </div>
                    <Button type="submit" :disabled="tokenProcessing">
                        <Key class="size-4" />
                        {{ tokenProcessing ? 'Creating…' : 'Create Token' }}
                    </Button>
                </Form>

                <!-- Tokens Table -->
                <div v-if="integrationApp.tokens.length > 0" class="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Prefix</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Last Used</TableHead>
                                <TableHead>Expires</TableHead>
                                <TableHead>Created</TableHead>
                                <TableHead class="w-12" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="token in integrationApp.tokens" :key="token.id">
                                <TableCell class="font-medium">{{ token.name }}</TableCell>
                                <TableCell>
                                    <code class="text-xs">{{ token.plain_text_prefix }}…</code>
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="token.revoked_at" variant="destructive">Revoked</Badge>
                                    <Badge v-else-if="!isTokenActive(token)" variant="secondary">Expired</Badge>
                                    <Badge v-else variant="default">Active</Badge>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ token.last_used_at ? new Date(token.last_used_at).toLocaleString() : 'Never' }}
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ token.expires_at ? new Date(token.expires_at).toLocaleDateString() : 'Never' }}
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ new Date(token.created_at).toLocaleDateString() }}
                                </TableCell>
                                <TableCell>
                                    <div v-if="isTokenActive(token)" class="flex items-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="confirmRotate(token.id)"
                                            title="Rotate token"
                                        >
                                            <RefreshCw class="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="confirmRevoke(token.id)"
                                            title="Revoke token"
                                        >
                                            <Trash2 class="size-4 text-destructive" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <p v-else class="text-sm text-muted-foreground">No tokens have been created yet.</p>
            </div>

            <!-- Danger Zone -->
            <div class="space-y-4 border-t pt-8">
                <Heading
                    variant="small"
                    title="Danger Zone"
                    description="Permanently delete this integration and all its tokens"
                />

                <Dialog v-model:open="showDeleteDialog">
                    <DialogTrigger as-child>
                        <Button variant="destructive">Delete Integration</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete {{ integrationApp.name }}?</DialogTitle>
                            <DialogDescription>
                                This will permanently delete the integration and revoke all associated tokens. Any side-apps using these tokens will stop working. This action cannot be undone.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" @click="showDeleteDialog = false">Cancel</Button>
                            <Button variant="destructive" @click="deleteApp">Delete</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Rotate Token Dialog -->
            <Dialog v-model:open="showRotateDialog">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Rotate Token?</DialogTitle>
                        <DialogDescription>
                            This will revoke the current token and create a new replacement token with the same name. The old token will immediately stop working.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="showRotateDialog = false">Cancel</Button>
                        <Button @click="rotateTokenAction(rotateTokenId!)">Rotate</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Revoke Token Dialog -->
            <Dialog v-model:open="showRevokeDialog">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Revoke Token?</DialogTitle>
                        <DialogDescription>
                            This token will be immediately revoked and can no longer be used for API access. This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="showRevokeDialog = false">Cancel</Button>
                        <Button variant="destructive" @click="revokeToken(revokeTokenId!)">Revoke</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
