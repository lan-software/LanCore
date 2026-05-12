import { onUnmounted  } from 'vue';
import type {Ref} from 'vue';
import type { SeatPlanImperativeHandle } from './types';

type ViewerInstance = SeatPlanImperativeHandle & {
    /* Vue surfaces ref-exposed methods directly on the instance. */
};

type ReadyCallback = () => void;

type Pending =
    | { kind: 'fitToVenue'; args: Parameters<SeatPlanImperativeHandle['fitToVenue']> }
    | {
          kind: 'zoomToBlock';
          args: Parameters<SeatPlanImperativeHandle['zoomToBlock']>;
      }
    | {
          kind: 'zoomToSeat';
          args: Parameters<SeatPlanImperativeHandle['zoomToSeat']>;
      }
    | {
          kind: 'zoomToBoundingBox';
          args: Parameters<SeatPlanImperativeHandle['zoomToBoundingBox']>;
      }
    | { kind: 'pulseSeat'; args: Parameters<SeatPlanImperativeHandle['pulseSeat']> }
    | { kind: 'setView'; args: Parameters<SeatPlanImperativeHandle['setView']> };

export type SeatPlanViewerHandle = SeatPlanImperativeHandle & {
    /**
     * Register a callback to fire after the viewer's first paint AND after
     * every subsequent re-emit of `ready` from the scene (which happens
     * after deep plan replacement). The callback receives no arguments;
     * use it to wire focus / pulse flows that should re-run once the
     * geometry is laid out.
     */
    onReady(cb: ReadyCallback): void;
};

/**
 * Composable that wraps a template ref to a SeatPlanViewer (or anything
 * implementing SeatPlanImperativeHandle) and gives you a typed control
 * surface. Calls placed before the viewer mounts are queued and flushed
 * on the first `ready` (matching what consumers like Welcome.vue need:
 * call `zoomToSeat` / `pulseSeat` from `onMounted` even before the SVG
 * has run its initial fit).
 *
 * @example
 *   const viewerRef = ref<InstanceType<typeof SeatPlanViewer>>();
 *   const viewer = useSeatPlanViewer(viewerRef);
 *
 *   viewer.onReady(() => {
 *     if (focusSeatId) {
 *       viewer.zoomToSeat(focusSeatId);
 *       viewer.pulseSeat(focusSeatId);
 *     }
 *   });
 *
 *   <SeatPlanViewer ref="viewerRef" :plan="plan" @ready="viewer.__notifyReady" />
 *
 * The composable hooks `@ready` automatically when used with the
 * companion helper {@link wireReady} — Vue templates expose `__notifyReady`
 * on the returned handle so the viewer's `@ready` payload reaches us.
 */
export function useSeatPlanViewer(
    ref: Ref<ViewerInstance | null | undefined>,
): SeatPlanViewerHandle {
    const queue: Pending[] = [];
    const readyCallbacks: ReadyCallback[] = [];
    let everReady = false;

    function instance(): ViewerInstance | null {
        return ref.value ?? null;
    }

    function flushQueue(): void {
        const inst = instance();

        if (!inst) {
            return;
        }

        while (queue.length > 0) {
            const next = queue.shift()!;

            switch (next.kind) {
                case 'fitToVenue':
                    inst.fitToVenue(...next.args);
                    break;
                case 'zoomToBlock':
                    inst.zoomToBlock(...next.args);
                    break;
                case 'zoomToSeat':
                    inst.zoomToSeat(...next.args);
                    break;
                case 'zoomToBoundingBox':
                    inst.zoomToBoundingBox(...next.args);
                    break;
                case 'pulseSeat':
                    inst.pulseSeat(...next.args);
                    break;
                case 'setView':
                    inst.setView(...next.args);
                    break;
            }
        }
    }

    function notifyReady(): void {
        everReady = true;
        flushQueue();

        for (const cb of readyCallbacks) {
            try {
                cb();
            } catch (err) {
                 
                console.error('[useSeatPlanViewer] onReady callback threw', err);
            }
        }
    }

    /**
     * The composable can't subscribe to the child viewer's events from
     * inside (no template), so the consumer must wire
     * `@ready="notifyReady(viewer)"` (or `@ready="viewer.notifyReady"`)
     * on the viewer element. Without that hookup the queued calls and
     * onReady callbacks never fire — by design, so timing stays explicit.
     */

    onUnmounted(() => {
        readyCallbacks.length = 0;
        queue.length = 0;
    });

    function callOrQueue<K extends Pending['kind']>(
        kind: K,
        args: Extract<Pending, { kind: K }>['args'],
    ): void {
        const inst = instance();

        if (inst && everReady) {
            switch (kind) {
                case 'fitToVenue':
                    inst.fitToVenue(...(args as Parameters<typeof inst.fitToVenue>));
                    break;
                case 'zoomToBlock':
                    inst.zoomToBlock(...(args as Parameters<typeof inst.zoomToBlock>));
                    break;
                case 'zoomToSeat':
                    inst.zoomToSeat(...(args as Parameters<typeof inst.zoomToSeat>));
                    break;
                case 'zoomToBoundingBox':
                    inst.zoomToBoundingBox(
                        ...(args as Parameters<typeof inst.zoomToBoundingBox>),
                    );
                    break;
                case 'pulseSeat':
                    inst.pulseSeat(...(args as Parameters<typeof inst.pulseSeat>));
                    break;
                case 'setView':
                    inst.setView(...(args as Parameters<typeof inst.setView>));
                    break;
            }
        } else {
            queue.push({ kind, args } as Pending);
        }
    }

    const handle: SeatPlanViewerHandle & { notifyReady?: ReadyCallback } = {
        fitToVenue(options): void {
            callOrQueue('fitToVenue', [options]);
        },
        zoomToBlock(blockId, options): void {
            callOrQueue('zoomToBlock', [blockId, options]);
        },
        zoomToSeat(seatId, options): void {
            callOrQueue('zoomToSeat', [seatId, options]);
        },
        zoomToBoundingBox(bbox, options): void {
            callOrQueue('zoomToBoundingBox', [bbox, options]);
        },
        pulseSeat(seatId, options): void {
            callOrQueue('pulseSeat', [seatId, options]);
        },
        getSeatScreenRect(seatId): DOMRect | null {
            return instance()?.getSeatScreenRect(seatId) ?? null;
        },
        getView() {
            return instance()?.getView() ?? { panX: 0, panY: 0, zoom: 1 };
        },
        setView(view, options): void {
            callOrQueue('setView', [view, options]);
        },
        onReady(cb: ReadyCallback): void {
            readyCallbacks.push(cb);

            if (everReady) {
                try {
                    cb();
                } catch (err) {
                     
                    console.error('[useSeatPlanViewer] onReady callback threw', err);
                }
            }
        },
    };

    /* Expose notifier for `@ready="viewer.notifyReady"`. Marked optional in
     * the type so consumers can ignore it if they wire `@ready` themselves. */
    Object.defineProperty(handle, 'notifyReady', {
        value: notifyReady,
        enumerable: false,
    });

    return handle;
}

/**
 * Public alias for the ready notifier. Usage:
 *   <SeatPlanViewer @ready="notifyReady(viewer)" />
 */
export function notifyReady(handle: SeatPlanViewerHandle): void {
    const fn = (handle as SeatPlanViewerHandle & { notifyReady?: ReadyCallback })
        .notifyReady;

    if (typeof fn === 'function') {
        fn();
    }
}
