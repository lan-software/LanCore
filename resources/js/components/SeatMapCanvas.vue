<script setup lang="ts">
import type { SeatMapCanvas as SeatMapCanvasClass } from '@alisaitteke/seatmap-canvas';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { SeatPlanBlock, SeatPlanData, SeatPlanSeat } from '@/types/domain';

interface ZoneSeat {
    seat_number?: string;
    seat_guid: string;
    position: { x: number; y: number };
    category: string;
}

interface ZoneRow {
    position: { x: number; y: number };
    row_number: string;
    row_number_position?: string;
    seats: ZoneSeat[];
}

interface Zone {
    name: string;
    position: { x: number; y: number };
    rows: ZoneRow[];
}

interface ZoneCategory {
    name: string;
    color: string;
}

interface ZoneFormat {
    categories?: ZoneCategory[];
    zones: Zone[];
}

const props = withDefaults(
    defineProps<{
        data: SeatPlanData;
        options?: Record<string, unknown>;
    }>(),
    {
        options: () => ({}),
    },
);

const emit = defineEmits<{
    'seat-click': [seat: unknown];
    /**
     * Fires after each successful init of the underlying canvas. The parent
     * can use this as the cue to re-apply any visual selection it tracks,
     * because our `watch(() => props.data)` re-builds the SVG from scratch on
     * every data change, which wipes whatever selection existed beforehand.
     */
    ready: [];
}>();

const containerRef = ref<HTMLDivElement | null>(null);

/* Plan-level background URL if the `data` shape carries one. Rendered as a
 * sibling <img> behind the library's SVG because the library only supports
 * per-block backgrounds. */
const planBackgroundImage = computed<string | null>(() => {
    const maybeUrl = (
        props.data as SeatPlanData & { background_image_url?: string | null }
    ).background_image_url;

    return typeof maybeUrl === 'string' && maybeUrl !== '' ? maybeUrl : null;
});
let seatmapInstance: SeatMapCanvasClass | null = null;

function isZoneFormat(data: SeatPlanData): data is SeatPlanData & ZoneFormat {
    return (
        'zones' in data &&
        Array.isArray((data as Record<string, unknown>).zones)
    );
}

/**
 * Convert zones/rows/seats format to native seatmap-canvas blocks format.
 * The library's built-in pretix parser has a bug that accumulates row positions,
 * so we handle the conversion ourselves.
 */
function convertZonesToBlocks(data: ZoneFormat): SeatPlanBlock[] {
    const categoryColors = new Map<string, string>();

    if (data.categories) {
        for (const cat of data.categories) {
            categoryColors.set(cat.name, cat.color);
        }
    }

    const blockMap = new Map<string, SeatPlanBlock>();

    for (const zone of data.zones) {
        const zoneX = zone.position.x;
        const zoneY = zone.position.y;

        for (const row of zone.rows) {
            const rowX = zoneX + row.position.x;
            const rowY = zoneY + row.position.y;

            for (const seat of row.seats) {
                const blockId = `${seat.category}-${row.row_number}-${row.row_number_position ?? 'start'}`;

                if (!blockMap.has(blockId)) {
                    blockMap.set(blockId, {
                        id: blockId,
                        title: seat.category,
                        color: categoryColors.get(seat.category) ?? '#2c2828',
                        seats: [],
                        labels: [],
                    });
                }

                const block = blockMap.get(blockId)!;
                block.seats.push({
                    id: seat.seat_guid,
                    x: rowX + seat.position.x,
                    y: rowY + seat.position.y,
                    title: seat.seat_guid,
                    salable: true,
                });
            }
        }
    }

    const blocks = Array.from(blockMap.values());

    for (const block of blocks) {
        block.seats.sort((a, b) => (a.x === b.x ? a.y - b.y : a.x - b.x));
    }

    return blocks;
}

function getBlocks(): SeatPlanBlock[] {
    const raw = isZoneFormat(props.data)
        ? convertZonesToBlocks(props.data)
        : (props.data.blocks ?? []);

    // CRITICAL: the library mutates seat objects to track selection state
    // (`this.item.selected = true` inside Seat.prototype.select). If we pass
    // Vue-reactive objects, those mutations trigger the parent's computed to
    // re-run, which fires this component's `watch(() => props.data, …)`,
    // which re-inits the canvas — wiping the very selection we just applied.
    // Deep-clone so the library's internal mutations stay out of Vue's graph.
    const cloned = deepCloneBlocks(raw);

    // Filter out blocks with no seats. An empty block has no visible content
    // and, more importantly, breaks the library's internal bbox math: its
    // zoomManager computes venue bounds from seat positions, and an empty
    // `seats` array leaves that block's bounds as NaN, which then poisons
    // the venue-fit zoom for the whole canvas. Symptom: the canvas renders
    // as an empty viewport even when sibling blocks have seats. Admin "empty"
    // blocks are still editable in the admin editor — this filter only runs
    // on the read-side wrapper the Picker / Welcome consume.
    const visible = cloned.filter((block) => (block.seats?.length ?? 0) > 0);

    // The library's BlockModel reads `background_image`/`background_opacity`/
    // `background_fit`/etc. — our wire shape carries it as `background_image_url`.
    // Rename so the library actually picks up per-block backgrounds.
    return visible.map((block) => {
        const withBg = block as SeatPlanBlock & {
            background_image_url?: string | null;
            background_image?: string | null;
            background_opacity?: number;
            background_fit?: 'cover' | 'contain' | 'fill' | 'none';
        };

        if (withBg.background_image_url) {
            withBg.background_image = withBg.background_image_url;

            if (withBg.background_opacity === undefined) {
                withBg.background_opacity = 1;
            }

            if (withBg.background_fit === undefined) {
                withBg.background_fit = 'cover';
            }
        }

        delete withBg.background_image_url;

        return withBg;
    });
}

function deepCloneBlocks(blocks: SeatPlanBlock[]): SeatPlanBlock[] {
    // JSON round-trip — safer than structuredClone on Vue reactive proxies
    // (some Proxy configurations throw DataCloneError). Our seat plan data
    // is JSON-serializable by design (stored as JSONB server-side).
    return JSON.parse(JSON.stringify(blocks));
}

async function initSeatmap(): Promise<void> {
    if (!containerRef.value) {
        return;
    }

    let blocks: SeatPlanBlock[];

    try {
        blocks = getBlocks();
    } catch (error) {
        console.error('[SeatMapCanvas] Failed to prepare seat blocks:', error);

        return;
    }

    if (blocks.length === 0) {
        return;
    }

    destroySeatmap();

    const { SeatMapCanvas } = await import('@alisaitteke/seatmap-canvas');

    // Detect effective theme — the app toggles the "dark" class on <html> via
    // the appearance setting. Pass a matching legend font_color to the library
    // so the inline-SVG legend ("Non Selectable Seats", "Selectable", …)
    // renders with readable contrast. A CSS !important override in <style>
    // below backstops this for the hardcoded `dark:fill-white` class the
    // library emits regardless.
    const isDark =
        typeof document !== 'undefined' &&
        document.documentElement.classList.contains('dark');
    const legendFontColor = isDark ? '#f3f4f6' : '#111827';
    const labelFontColor = isDark ? '#e5e7eb' : '#1f2937';

    const defaultOptions = {
        legend: true,
        style: {
            seat: {
                radius: 12,
                color: '#6796ff',
                hover: '#5671ff',
                selected: '#56aa45',
                not_salable: '#424747',
            },
            block: {
                fill: '#e2e2e2',
                stroke: '#e2e2e2',
            },
            /* Library reads `style.label.bg` and `style.label.font_size`
             * (not `background` / `fontSize` — different from the seat style
             * keys). `bg: transparent` + `radius: 0` removes the white pill
             * and leaves only the text. Font color follows the app theme. */
            label: {
                color: labelFontColor,
                radius: 0,
                font_size: '12px',
                bg: 'transparent',
            },
            legend: {
                font_color: legendFontColor,
            },
        },
    };

    const mergedOptions = {
        ...defaultOptions,
        ...props.options,
        style: {
            ...defaultOptions.style,
            ...((props.options?.style as Record<string, unknown>) ?? {}),
        },
    };

    try {
        seatmapInstance = new SeatMapCanvas(containerRef.value, mergedOptions);
        seatmapInstance.data.replaceData(
            blocks as unknown as Record<string, unknown>[],
        );
    } catch (error) {
        console.error('[SeatMapCanvas] Library init failed:', error, {
            blockCount: blocks.length,
            seatCount: blocks.reduce((n, b) => n + (b.seats?.length ?? 0), 0),
            firstBlock: blocks[0],
        });
        seatmapInstance = null;

        return;
    }

    // Library v2.7.1 has NO click binding on seat elements — the README
    // documents a `seat_click` event but the published bundle never dispatches
    // one. We implement click detection ourselves via DOM event delegation on
    // the container and synthesize a payload that matches the library's public
    // SeatClickEvent shape (id, salable, isSelected/select/unSelect methods).
    wireSeatClicks(blocks);

    emit('ready');
}

const seatColors = computed(() => {
    const style = (props.options?.style as Record<string, unknown> | undefined)
        ?.seat as Record<string, string> | undefined;

    return {
        default: style?.color ?? '#6796ff',
        selected: style?.selected ?? '#56aa45',
        notSalable: style?.not_salable ?? '#424747',
    };
});

function findSeatData(
    blocks: SeatPlanBlock[],
    blockId: string,
    seatId: string,
): SeatPlanSeat | null {
    for (const block of blocks) {
        if (String(block.id) !== blockId) {
            continue;
        }

        for (const seat of block.seats) {
            if (String(seat.id) === seatId) {
                return seat;
            }
        }
    }

    return null;
}

function wireSeatClicks(blocks: SeatPlanBlock[]): void {
    if (!containerRef.value) {
        return;
    }

    console.info(
        '[SeatMapCanvas] Wiring DOM click delegation. Blocks:',
        blocks.length,
        'Total seats:',
        blocks.reduce((n, b) => n + b.seats.length, 0),
    );

    containerRef.value.addEventListener('click', (event: MouseEvent): void => {
        // Library v2.7.1 puts an SVG mask layer on top of seats which
        // swallows clicks at the venue zoom level. Use elementFromPoint to
        // walk past any overlapping masks at the click coordinates and
        // find the actual seat underneath.
        let seatNode: SVGGElement | null = null;
        const root = containerRef.value!;

        const hitStack = (
            typeof document.elementsFromPoint === 'function'
                ? document.elementsFromPoint(event.clientX, event.clientY)
                : [event.target as Element | null].filter(Boolean)
        ) as Element[];

        for (const el of hitStack) {
            if (!root.contains(el)) {
                continue;
            }

            const candidate = el.closest<SVGGElement>('g.seat');

            if (candidate && root.contains(candidate)) {
                seatNode = candidate;
                break;
            }
        }

        if (!seatNode) {
            console.debug(
                '[SeatMapCanvas] click ignored — no seat under pointer',
                {
                    x: event.clientX,
                    y: event.clientY,
                    hitStack: hitStack.map(
                        (e) =>
                            e.tagName + '.' + (e.getAttribute('class') ?? ''),
                    ),
                },
            );

            return;
        }

        const seatId = seatNode.getAttribute('id');
        const circle = seatNode.querySelector<SVGElement>(
            '.seat-circle,.seat-rect,.seat-path',
        );
        const blockId = circle?.getAttribute('block-id') ?? null;

        if (!seatId || !blockId) {
            console.warn('[SeatMapCanvas] seat element missing id/block-id', {
                seatId,
                blockId,
                seatNode,
            });

            return;
        }

        const data = findSeatData(blocks, blockId, seatId);

        if (!data) {
            console.warn('[SeatMapCanvas] click target not in block data', {
                seatId,
                blockId,
            });

            return;
        }

        console.info('[SeatMapCanvas] seat clicked', {
            blockId,
            seatId,
            title: data.title,
            salable: data.salable,
        });

        const fillTarget = circle;
        const synthetic = {
            id: seatId,
            title: data.title,
            salable: data.salable !== false,
            x: data.x,
            y: data.y,
            isSelected: (): boolean => seatNode.classList.contains('selected'),
            select: (): void => {
                seatNode.classList.add('selected');
                fillTarget?.setAttribute('fill', seatColors.value.selected);
                console.info('[SeatMapCanvas] seat.select() applied', seatId);
            },
            unSelect: (): void => {
                seatNode.classList.remove('selected');
                fillTarget?.setAttribute(
                    'fill',
                    data.salable === false
                        ? seatColors.value.notSalable
                        : seatColors.value.default,
                );
                console.info('[SeatMapCanvas] seat.unSelect() applied', seatId);
            },
        };

        emit('seat-click', synthetic);
    });
}

defineExpose({
    /**
     * Access the underlying library instance for programmatic control
     * (getSelectedSeats, getSeat, zoomToBlock, …). Returns null if the
     * canvas hasn't finished its async init yet.
     */
    getInstance(): SeatMapCanvasClass | null {
        return seatmapInstance;
    },

    /**
     * Reset viewport to the full venue — useful as a toolbar "Reset view"
     * button.
     */
    resetView(): void {
        const zm = (
            seatmapInstance as unknown as {
                zoomManager?: { zoomToVenue: (animated?: boolean) => void };
            } | null
        )?.zoomManager;
        zm?.zoomToVenue?.(true);
    },

    /**
     * Zoom the viewport to whatever seats are currently marked selected
     * on the canvas.
     */
    zoomToSelection(): void {
        const zm = (
            seatmapInstance as unknown as {
                zoomManager?: { zoomToSelection: (animated?: boolean) => void };
            } | null
        )?.zoomManager;
        zm?.zoomToSelection?.(true);
    },

    /**
     * Zoom the viewport to a specific block by id (e.g. a ticket's row).
     */
    zoomToBlock(blockId: string): void {
        const zm = (
            seatmapInstance as unknown as {
                zoomManager?: {
                    zoomToBlock: (id: string, animated?: boolean) => void;
                };
            } | null
        )?.zoomManager;
        zm?.zoomToBlock?.(blockId, true);
    },
});

function destroySeatmap(): void {
    if (seatmapInstance && containerRef.value) {
        containerRef.value.innerHTML = '';
        seatmapInstance = null;
    }
}

onMounted(() => {
    initSeatmap();
    wireThemeObserver();
});

onBeforeUnmount(() => {
    destroySeatmap();
    themeObserver?.disconnect();
    themeObserver = null;
});

watch(
    () => props.data,
    () => {
        initSeatmap();
    },
);

/**
 * Observe changes to `<html class="dark">` so the legend re-renders with the
 * correct font_color when the user toggles the theme. The init path already
 * reads the dark class; we just need to re-run init on change. The CSS
 * override in <style> handles frames between re-init.
 */
let themeObserver: MutationObserver | null = null;
function wireThemeObserver(): void {
    if (
        typeof document === 'undefined' ||
        typeof MutationObserver === 'undefined'
    ) {
        return;
    }

    const root = document.documentElement;
    let lastDark = root.classList.contains('dark');

    themeObserver = new MutationObserver(() => {
        const isDark = root.classList.contains('dark');

        if (isDark !== lastDark) {
            lastDark = isDark;
            initSeatmap();
        }
    });

    themeObserver.observe(root, {
        attributes: true,
        attributeFilter: ['class'],
    });
}
</script>

<template>
    <!--
      Plan-level background image lives outside the library's SVG because
      the library's BlockModel only supports per-block backgrounds. The
      wrapper is `position: relative` so the <img> and the SVG stack.
      `pointer-events: none` on the image keeps seat clicks working.
     -->
    <div class="seatmap-wrapper">
        <img
            v-if="planBackgroundImage"
            class="seatmap-plan-bg"
            :src="planBackgroundImage"
            alt=""
            draggable="false"
        />
        <div ref="containerRef" class="seatmap-container" />
    </div>
</template>

<style>
@import '@alisaitteke/seatmap-canvas/dist/esm/seatmap.canvas.css';

.seatmap-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
}

.seatmap-plan-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    pointer-events: none;
    opacity: 0.6;
    z-index: 0;
}

.seatmap-container {
    position: relative;
    width: 100%;
    height: 100%;
    z-index: 1;
}

.seatmap-container svg {
    width: 100%;
    height: 100%;
}

/*
 * Library v2.7.1 overlays several SVG layers on top of the seats to drive
 * its zoom-in flow: a block-level-mask + seat-level-mask inside .masks, and
 * an invisible block-hull hit path inside .bounds. All intercept pointer
 * events at the venue zoom level, which blocks direct seat clicks. For our
 * direct-pick UX we want seats clickable at any zoom level, so we render
 * these overlays visible but non-interactive.
 */
.seatmap-container .seatmap-svg .stage .blocks .block .masks,
.seatmap-container .seatmap-svg .stage .blocks .block .masks *,
.seatmap-container .seatmap-svg .stage .blocks .block .bounds,
.seatmap-container .seatmap-svg .stage .blocks .block .bounds * {
    pointer-events: none;
}

.seatmap-container .seatmap-svg .stage .blocks .block .seats .seat {
    cursor: pointer;
}

/*
 * Library v2.7.1 emits legend <text> nodes with a hardcoded
 * `dark:fill-white` class and an inline `fill` set from its own config.
 * Force the fill to follow the app's dark/light theme — belt + braces
 * alongside the theme-aware `font_color` option passed from the script.
 */
html:not(.dark) .seatmap-container .legend text {
    fill: #111827 !important;
}
html.dark .seatmap-container .legend text {
    fill: #f3f4f6 !important;
}
</style>
