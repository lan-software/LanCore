import type {
    SeatPlanBlock,
    SeatPlanLabel,
    SeatPlanRow,
    SeatPlanSeat,
} from '@/types/domain';

export type EditorSeat = SeatPlanSeat;
export type EditorRow = SeatPlanRow;
export type EditorLabel = SeatPlanLabel;
export type EditorBlock = SeatPlanBlock & { rows: SeatPlanRow[] };

export type EditorPlan = {
    id: number;
    name: string;
    event_id: number;
    background_image_url?: string | null;
    /** Plan-level labels (SET-F-020); flattened into first block on the wire. */
    labels: EditorLabel[];
    blocks: EditorBlock[];
};

export type ToolMode =
    | 'select'
    | 'add-seat'
    | 'add-row'
    | 'add-label'
    | 'add-block'
    | 'delete'
    | 'pan';

export type EntityKind = 'seat' | 'row' | 'block' | 'label';

export type EntityRef = {
    kind: EntityKind;
    id: number | string;
    blockId?: number | string;
};

export type ViewState = {
    zoom: number;
    panX: number;
    panY: number;
    snapEnabled: boolean;
    gridSize: number;
    showGrid: boolean;
};

export type EditorSnapshot = {
    plan: EditorPlan;
    label: string;
    at: number;
};

export type IdMap = {
    blocks: Record<string, number>;
    rows: Record<string, number>;
    seats: Record<string, number>;
    labels: Record<string, number>;
};
