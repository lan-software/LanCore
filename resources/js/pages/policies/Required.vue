<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

interface RequiredPolicy {
    id: number;
    key: string;
    name: string;
    description: string | null;
    required_acceptance_version: {
        id: number;
        version_number: number;
        locale: string;
        content: string;
        public_statement: string | null;
    };
}

const props = defineProps<{
    policies: RequiredPolicy[];
    intendedUrl: string | null;
}>();

const expandedId = ref<number | null>(props.policies[0]?.id ?? null);

function toggle(id: number): void {
    expandedId.value = expandedId.value === id ? null : id;
}

const form = useForm<{ policy_version_ids: number[] }>({
    policy_version_ids: [],
});

function acceptAll(): void {
    form.policy_version_ids = props.policies.map(
        (p) => p.required_acceptance_version.id,
    );
    form.post('/policies/required/accept');
}

function acceptOne(versionId: number): void {
    form.policy_version_ids = [versionId];
    form.post('/policies/required/accept');
}
</script>

<template>
    <Head title="Required policies" />

    <div class="mx-auto flex min-h-screen max-w-3xl flex-col gap-6 p-6">
        <Heading
            title="Action required"
            description="Please review and accept the following policy updates to continue."
        />

        <div class="space-y-4">
            <div
                v-for="policy in policies"
                :key="policy.id"
                class="rounded-md border bg-card"
            >
                <button
                    class="flex w-full items-center justify-between p-4 text-left"
                    @click="toggle(policy.id)"
                >
                    <div>
                        <div class="font-medium">{{ policy.name }}</div>
                        <div
                            v-if="policy.description"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            {{ policy.description }}
                        </div>
                    </div>
                    <span class="text-xs text-muted-foreground">
                        v{{
                            policy.required_acceptance_version.version_number
                        }}
                        ({{ policy.required_acceptance_version.locale }})
                    </span>
                </button>

                <div v-if="expandedId === policy.id" class="border-t p-4">
                    <div
                        v-if="
                            policy.required_acceptance_version.public_statement
                        "
                        class="mb-4 rounded border-l-4 border-blue-400 bg-blue-50 p-3 text-sm dark:bg-blue-950/30"
                    >
                        <strong>From the operator:</strong>
                        {{
                            policy.required_acceptance_version.public_statement
                        }}
                    </div>
                    <pre
                        class="max-h-96 overflow-auto rounded bg-muted p-4 text-sm whitespace-pre-wrap"
                        >{{ policy.required_acceptance_version.content }}</pre
                    >
                    <div class="mt-4 flex justify-end">
                        <Button
                            :disabled="form.processing"
                            @click="
                                acceptOne(policy.required_acceptance_version.id)
                            "
                        >
                            Accept
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="policies.length > 1" class="flex justify-end">
            <Button :disabled="form.processing" @click="acceptAll">
                Accept all
            </Button>
        </div>

        <p v-if="intendedUrl" class="text-center text-xs text-muted-foreground">
            You'll be returned to your previous page after accepting.
        </p>
    </div>
</template>
