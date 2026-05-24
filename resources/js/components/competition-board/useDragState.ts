import { ref, provide, inject } from 'vue';
import type { Ref, InjectionKey } from 'vue';

export interface DragState {
    active: Ref<boolean>;
    tooltip: Ref<{ x: number; y: number; text: string } | null>;
    ghost: Ref<{
        x: number;
        widthSolid: number;
        widthReserve: number;
        color: string;
    } | null>;
}

const DRAG_STATE: InjectionKey<DragState> = Symbol(
    'competition-board:drag-state',
);

export function provideDragState(): DragState {
    const state: DragState = {
        active: ref(false),
        tooltip: ref(null),
        ghost: ref(null),
    };
    provide(DRAG_STATE, state);

    return state;
}

export function useDragState(): DragState {
    const state = inject(DRAG_STATE, null);

    if (!state) {
        // Fallback no-op state when used outside a board provider (e.g. unit tests).
        return {
            active: ref(false),
            tooltip: ref(null),
            ghost: ref(null),
        };
    }

    return state;
}
