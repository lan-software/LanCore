import type {
    SeatPlanBlock,
    SeatPlanData,
    SeatPlanLabel,
    SeatPlanSeat,
} from '@/types/domain';

export type ViewState = {
    panX: number;
    panY: number;
    zoom: number;
};

export type BoundingBox = {
    minX: number;
    minY: number;
    maxX: number;
    maxY: number;
};

export type SeatPalette = {
    /** Default fill for a salable seat. */
    salable: string;
    /** Fill for a non-salable seat (taken / category-blocked). */
    notSalable: string;
    /** Fill / outline for a selected seat. */
    selected: string;
    /** Outline color when hovering a seat. */
    hover: string;
    /** Fallback block fill when a block has no `color`. */
    blockFallback: string;
};

export const DEFAULT_PALETTE: SeatPalette = {
    salable: '#6796ff',
    notSalable: '#424747',
    selected: '#56aa45',
    hover: '#8fe100',
    blockFallback: '#2c3e50',
};

export type ColoringStrategy =
    | 'block-color'
    | ((seat: SeatPlanSeat, block: SeatPlanBlock) => string);

export type SeatPlanScenePlan = SeatPlanData & {
    background_image_url?: string | null;
    /**
     * Optional plan-level labels rendered alongside per-block labels. When
     * absent, labels are read only from each block. The picker resource
     * historically flattened these into the first block; the new wire shape
     * keeps them separated.
     */
    labels?: SeatPlanLabel[];
};

/**
 * Imperative handle exposed by SeatPlanScene + SeatPlanViewer. Consumers reach
 * it via a template ref or the `useSeatPlanViewer` composable.
 */
export type SeatPlanImperativeHandle = {
    fitToVenue(options?: { animated?: boolean; padding?: number }): void;
    zoomToBlock(
        blockId: string | string,
        options?: { animated?: boolean; padding?: number },
    ): void;
    zoomToSeat(
        seatId: string | string,
        options?: { animated?: boolean; padding?: number },
    ): void;
    zoomToBoundingBox(
        bbox: BoundingBox,
        options?: { animated?: boolean; padding?: number },
    ): void;
    pulseSeat(seatId: string | string, options?: { durationMs?: number }): void;
    getSeatScreenRect(seatId: string | string): DOMRect | null;
    getView(): ViewState;
    setView(view: Partial<ViewState>, options?: { animated?: boolean }): void;
};
