import { computed, type ComputedRef } from 'vue';
import type { CompetitionDto, EventDto, TimeSegment } from './types';

const BUSY_PX_PER_MIN = 2; // ~120 px per hour
const IDLE_PX_PER_MIN = 0.4; // ~24 px per hour (≈ 4h per length unit @ 96 px)
const BUSY_PADDING_MIN = 30;

interface UseTimeAxisResult {
    segments: ComputedRef<TimeSegment[]>;
    totalWidth: ComputedRef<number>;
    rangeStart: ComputedRef<number>;
    rangeEnd: ComputedRef<number>;
    xForTime: (iso: string | Date | number) => number;
    minuteForX: (x: number) => number;
    ticks: ComputedRef<Array<{ x: number; label: string; segment: 'busy' | 'idle' }>>;
}

export function useTimeAxis(
    event: () => EventDto,
    competitions: () => CompetitionDto[],
): UseTimeAxisResult {
    const rangeStart = computed(() => {
        const e = event();
        const stageStarts = competitions()
            .flatMap((c) => c.stage_schedules)
            .map((s) => (s.starts_at ? new Date(s.starts_at).getTime() : null))
            .filter((v): v is number => v !== null);
        const candidates = [
            e.start_date ? new Date(e.start_date).getTime() : null,
            ...stageStarts,
        ].filter((v): v is number => v !== null);
        if (candidates.length === 0) {
            return Date.now();
        }
        return Math.min(...candidates);
    });

    const rangeEnd = computed(() => {
        const e = event();
        const stageEnds = competitions()
            .flatMap((c) => c.stage_schedules)
            .map((s) => (s.ends_at ? new Date(s.ends_at).getTime() : null))
            .filter((v): v is number => v !== null);
        const candidates = [
            e.end_date ? new Date(e.end_date).getTime() : null,
            ...stageEnds,
        ].filter((v): v is number => v !== null);
        if (candidates.length === 0) {
            return rangeStart.value + 24 * 60 * 60 * 1000;
        }
        return Math.max(...candidates);
    });

    const busyIntervals = computed(() => {
        const raw = competitions()
            .flatMap((c) => c.stage_schedules)
            .filter((s) => s.starts_at && s.ends_at)
            .map((s) => ({
                start: new Date(s.starts_at!).getTime() - BUSY_PADDING_MIN * 60_000,
                end: new Date(s.ends_at!).getTime() + BUSY_PADDING_MIN * 60_000,
            }))
            .sort((a, b) => a.start - b.start);
        const merged: Array<{ start: number; end: number }> = [];
        for (const iv of raw) {
            if (merged.length > 0 && iv.start <= merged[merged.length - 1].end) {
                merged[merged.length - 1].end = Math.max(merged[merged.length - 1].end, iv.end);
            } else {
                merged.push({ ...iv });
            }
        }
        return merged;
    });

    const segments = computed<TimeSegment[]>(() => {
        const out: TimeSegment[] = [];
        let cursor = rangeStart.value;
        const end = rangeEnd.value;
        let offsetPx = 0;
        for (const busy of busyIntervals.value) {
            if (busy.start > cursor) {
                const minutes = (Math.min(busy.start, end) - cursor) / 60_000;
                if (minutes > 0) {
                    out.push({
                        start: cursor,
                        end: Math.min(busy.start, end),
                        pxPerMinute: IDLE_PX_PER_MIN,
                        classification: 'idle',
                        pxOffset: offsetPx,
                    });
                    offsetPx += minutes * IDLE_PX_PER_MIN;
                }
                cursor = Math.min(busy.start, end);
            }
            if (cursor >= end) break;
            const busyEnd = Math.min(busy.end, end);
            if (busyEnd > cursor) {
                const minutes = (busyEnd - cursor) / 60_000;
                out.push({
                    start: cursor,
                    end: busyEnd,
                    pxPerMinute: BUSY_PX_PER_MIN,
                    classification: 'busy',
                    pxOffset: offsetPx,
                });
                offsetPx += minutes * BUSY_PX_PER_MIN;
                cursor = busyEnd;
            }
        }
        if (cursor < end) {
            const minutes = (end - cursor) / 60_000;
            out.push({
                start: cursor,
                end,
                pxPerMinute: IDLE_PX_PER_MIN,
                classification: 'idle',
                pxOffset: offsetPx,
            });
        }
        return out;
    });

    const totalWidth = computed(() => {
        const segs = segments.value;
        if (segs.length === 0) return 0;
        const last = segs[segs.length - 1];
        return last.pxOffset + ((last.end - last.start) / 60_000) * last.pxPerMinute;
    });

    function xForTime(value: string | Date | number): number {
        const t = typeof value === 'number' ? value : new Date(value).getTime();
        for (const seg of segments.value) {
            if (t < seg.start) {
                return seg.pxOffset;
            }
            if (t <= seg.end) {
                return seg.pxOffset + ((t - seg.start) / 60_000) * seg.pxPerMinute;
            }
        }
        return totalWidth.value;
    }

    function minuteForX(x: number): number {
        let absoluteMinutes = 0;
        for (const seg of segments.value) {
            const segWidth = ((seg.end - seg.start) / 60_000) * seg.pxPerMinute;
            if (x <= seg.pxOffset + segWidth) {
                const localPx = Math.max(0, x - seg.pxOffset);
                const localMinutes = localPx / seg.pxPerMinute;
                return (seg.start - rangeStart.value) / 60_000 + localMinutes;
            }
            absoluteMinutes += (seg.end - seg.start) / 60_000;
        }
        return absoluteMinutes;
    }

    const ticks = computed(() => {
        const out: Array<{ x: number; label: string; segment: 'busy' | 'idle' }> = [];
        for (const seg of segments.value) {
            const stepHours = seg.classification === 'busy' ? 1 : 4;
            const stepMs = stepHours * 3_600_000;
            let t = Math.ceil(seg.start / stepMs) * stepMs;
            while (t < seg.end) {
                const x = seg.pxOffset + ((t - seg.start) / 60_000) * seg.pxPerMinute;
                const d = new Date(t);
                const label = `${d.toLocaleDateString(undefined, { weekday: 'short' })} ${String(d.getHours()).padStart(2, '0')}:00`;
                out.push({ x, label, segment: seg.classification });
                t += stepMs;
            }
        }
        return out;
    });

    return { segments, totalWidth, rangeStart, rangeEnd, xForTime, minuteForX, ticks };
}

export const TIME_AXIS_INJECTION = Symbol('competition-board:time-axis');
