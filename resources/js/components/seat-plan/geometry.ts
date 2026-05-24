import type { SeatPlanBlock } from '@/types/domain';
import type { BoundingBox, SeatPlanScenePlan } from './types';

/**
 * Bounding box of every renderable point in the plan (seat centres + label
 * positions, both per-block and plan-level). Returns null if the plan is
 * empty so the caller can decide on a fallback view.
 */
export function planBoundingBox(plan: SeatPlanScenePlan): BoundingBox | null {
    let minX = Number.POSITIVE_INFINITY;
    let maxX = Number.NEGATIVE_INFINITY;
    let minY = Number.POSITIVE_INFINITY;
    let maxY = Number.NEGATIVE_INFINITY;

    function accumulate(x: number, y: number): void {
        if (x < minX) {
            minX = x;
        }

        if (x > maxX) {
            maxX = x;
        }

        if (y < minY) {
            minY = y;
        }

        if (y > maxY) {
            maxY = y;
        }
    }

    for (const block of plan.blocks ?? []) {
        for (const seat of block.seats) {
            accumulate(seat.x, seat.y);
        }

        for (const label of block.labels) {
            accumulate(label.x, label.y);
        }
    }

    for (const label of plan.labels ?? []) {
        accumulate(label.x, label.y);
    }

    if (!Number.isFinite(minX)) {
        return null;
    }

    return { minX, maxX, minY, maxY };
}

export function blockBoundingBox(block: SeatPlanBlock): BoundingBox | null {
    let minX = Number.POSITIVE_INFINITY;
    let maxX = Number.NEGATIVE_INFINITY;
    let minY = Number.POSITIVE_INFINITY;
    let maxY = Number.NEGATIVE_INFINITY;

    for (const seat of block.seats) {
        if (seat.x < minX) {
            minX = seat.x;
        }

        if (seat.x > maxX) {
            maxX = seat.x;
        }

        if (seat.y < minY) {
            minY = seat.y;
        }

        if (seat.y > maxY) {
            maxY = seat.y;
        }
    }

    for (const label of block.labels) {
        if (label.x < minX) {
            minX = label.x;
        }

        if (label.x > maxX) {
            maxX = label.x;
        }

        if (label.y < minY) {
            minY = label.y;
        }

        if (label.y > maxY) {
            maxY = label.y;
        }
    }

    if (!Number.isFinite(minX)) {
        return null;
    }

    return { minX, maxX, minY, maxY };
}

/**
 * Compute zoom + pan that fit `bbox` into a fixed-size viewBox (1600×1000)
 * with a fractional `padding` margin around the bbox (default 1.2 = 20%
 * extra room on each side).
 *
 * Returned `{ panX, panY, zoom }` plug into the same `worldTransform` math
 * used by SeatPlanScene:
 *   svg = (world - pan) * zoom + viewCenter
 */
export function fitViewToBbox(
    bbox: BoundingBox,
    options: { padding?: number; minZoom?: number; maxZoom?: number } = {},
): { panX: number; panY: number; zoom: number } {
    const { padding = 1.2, minZoom = 0.1, maxZoom = 5 } = options;
    const centerX = (bbox.minX + bbox.maxX) / 2;
    const centerY = (bbox.minY + bbox.maxY) / 2;
    const width = Math.max(bbox.maxX - bbox.minX, 200);
    const height = Math.max(bbox.maxY - bbox.minY, 200);
    const zoomFit = Math.min(
        1600 / (width * padding),
        1000 / (height * padding),
    );

    return {
        panX: centerX,
        panY: centerY,
        zoom: Math.min(Math.max(zoomFit, minZoom), maxZoom),
    };
}
