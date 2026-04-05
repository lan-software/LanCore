<script setup lang="ts">
import { ref } from 'vue';
import { Copy, Check, Eye, EyeOff, Loader2, Server, AlertCircle } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { OrchestrationJob } from '@/types/domain';

const props = defineProps<{
    job: OrchestrationJob;
}>();

const showPassword = ref(false);
const copied = ref(false);

const statusConfig: Record<string, { label: string; color: string; icon: typeof Loader2 }> = {
    pending: { label: 'Waiting for server...', color: 'text-gray-500', icon: Loader2 },
    selecting_server: { label: 'Finding server...', color: 'text-yellow-500', icon: Loader2 },
    deploying: { label: 'Setting up server...', color: 'text-blue-500', icon: Loader2 },
    active: { label: 'Server ready', color: 'text-green-500', icon: Server },
    completed: { label: 'Match completed', color: 'text-emerald-500', icon: Check },
    failed: { label: 'Server setup failed', color: 'text-red-500', icon: AlertCircle },
    cancelled: { label: 'Cancelled', color: 'text-gray-400', icon: AlertCircle },
};

const config = statusConfig[props.job.status] ?? statusConfig.pending;
const isLoading = ['pending', 'selecting_server', 'deploying'].includes(props.job.status);

function copyAddress() {
    if (!props.job.game_server) return;
    const address = `${props.job.game_server.host}:${props.job.game_server.port}`;
    navigator.clipboard.writeText(address);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}
</script>

<template>
    <div class="rounded-lg border p-4">
        <div class="flex items-center gap-2">
            <component
                :is="config.icon"
                :class="[config.color, 'size-5', isLoading ? 'animate-spin' : '']"
            />
            <span class="text-sm font-medium">{{ config.label }}</span>
        </div>

        <div v-if="job.status === 'active' && job.game_server" class="mt-3 space-y-2">
            <div class="flex items-center gap-2">
                <code class="rounded bg-muted px-2 py-1 font-mono text-sm">
                    {{ job.game_server.host }}:{{ job.game_server.port }}
                </code>
                <Button variant="ghost" size="sm" @click="copyAddress">
                    <Check v-if="copied" class="size-4 text-green-500" />
                    <Copy v-else class="size-4" />
                </Button>
            </div>

            <div v-if="job.game_server.credentials?.server_password" class="flex items-center gap-2">
                <span class="text-xs text-muted-foreground">Password:</span>
                <code class="rounded bg-muted px-2 py-1 font-mono text-sm">
                    {{ showPassword ? job.game_server.credentials.server_password : '********' }}
                </code>
                <Button variant="ghost" size="sm" @click="showPassword = !showPassword">
                    <EyeOff v-if="showPassword" class="size-4" />
                    <Eye v-else class="size-4" />
                </Button>
            </div>
        </div>

        <p v-if="job.status === 'failed' && job.error_message" class="mt-2 text-xs text-red-500">
            {{ job.error_message }}
        </p>
    </div>
</template>
