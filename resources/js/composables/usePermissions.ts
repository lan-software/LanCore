// @see docs/mil-std-498/SRS.md USR-F-019
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { PermissionValue } from '@/types';

export function usePermissions() {
    const page = usePage();
    const permissions = computed<string[]>(
        () => (page.props.permissions as string[]) ?? [],
    );

    const can = (permission: PermissionValue) =>
        permissions.value.includes(permission);
    const canAny = (...perms: PermissionValue[]) => perms.some((p) => can(p));

    return { can, canAny, permissions };
}
