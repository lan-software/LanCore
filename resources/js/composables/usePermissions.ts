import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();
    const permissions = computed<string[]>(
        () => (page.props.permissions as string[]) ?? [],
    );

    const can = (permission: string) => permissions.value.includes(permission);
    const canAny = (...perms: string[]) => perms.some((p) => can(p));

    return { can, canAny, permissions };
}
