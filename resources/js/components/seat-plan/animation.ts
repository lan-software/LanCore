import type { ViewState } from './types';

export type Cancel = () => void;

function easeOutCubic(t: number): number {
    return 1 - Math.pow(1 - t, 3);
}

/**
 * Tween a `ViewState` from `from` to `to` over `durationMs`, calling
 * `onFrame` with the interpolated value on each animation frame. Returns a
 * cancel function. If `durationMs <= 0`, calls `onFrame(to)` once and resolves.
 */
export function tweenView(
    from: ViewState,
    to: ViewState,
    durationMs: number,
    onFrame: (next: ViewState) => void,
): Cancel {
    if (durationMs <= 0 || typeof requestAnimationFrame !== 'function') {
        onFrame(to);

        return () => {
            /* no-op */
        };
    }

    const start = performance.now();
    let rafId: number | null = null;
    let cancelled = false;

    function tick(now: number): void {
        if (cancelled) {
            return;
        }

        const elapsed = now - start;
        const progress = Math.min(1, elapsed / durationMs);
        const t = easeOutCubic(progress);

        onFrame({
            panX: from.panX + (to.panX - from.panX) * t,
            panY: from.panY + (to.panY - from.panY) * t,
            zoom: from.zoom + (to.zoom - from.zoom) * t,
        });

        if (progress < 1) {
            rafId = requestAnimationFrame(tick);
        } else {
            rafId = null;
        }
    }

    rafId = requestAnimationFrame(tick);

    return () => {
        cancelled = true;

        if (rafId !== null) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
    };
}
