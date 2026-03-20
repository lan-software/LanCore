<script setup lang="ts">
import UserController from '@/actions/App/Http/Controllers/Users/UserController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as usersIndexRoute } from '@/routes/users'
import type { BreadcrumbItem } from '@/types'
import type { Role, User } from '@/types/auth'
import { Form, Head, Link } from '@inertiajs/vue3'

const props = defineProps<{
    user: User
    availableRoles: Role[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: usersIndexRoute().url },
    { title: 'Users', href: usersIndexRoute().url },
    { title: props.user.name, href: UserController.show(props.user.id).url },
]

function hasRole(roleName: string): boolean {
    return props.user.roles.some((r) => r.name === roleName)
}
</script>

<template>
    <Head :title="`Edit ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
            <!-- Back link -->
            <div>
                <Link
                    :href="usersIndexRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Users
                </Link>
            </div>

            <Form
                v-bind="UserController.update.form(user.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <!-- Profile section -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Profile"
                        description="Update the user's name and email address"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="email"
                            placeholder="Email address"
                        />
                        <InputError :message="errors.email" />
                    </div>
                </div>

                <!-- Password section -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Password"
                        description="Leave blank to keep the current password"
                    />

                    <div class="grid gap-2">
                        <Label for="password">New password</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            placeholder="New password"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">Confirm password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            placeholder="Confirm new password"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>
                </div>

                <!-- Roles section -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Roles"
                        description="Assign roles to this user"
                    />

                    <div class="space-y-2">
                        <div
                            v-for="role in availableRoles"
                            :key="role.id"
                            class="flex items-center gap-2"
                        >
                            <Checkbox
                                :id="`role-${role.id}`"
                                :name="`role_names[]`"
                                :value="role.name"
                                :default-value="hasRole(role.name)"
                            />
                            <Label
                                :for="`role-${role.id}`"
                                class="cursor-pointer"
                            >
                                {{ role.label }}
                            </Label>
                        </div>
                        <InputError :message="errors.role_names" />
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4">
                    <Button
                        type="submit"
                        :disabled="processing"
                    >
                        Save changes
                    </Button>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-show="recentlySuccessful"
                            class="text-sm text-muted-foreground"
                        >
                            Saved.
                        </p>
                    </Transition>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
