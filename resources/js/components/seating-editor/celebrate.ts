import confetti from 'canvas-confetti';
import type { EditorPlan } from './editor-types';

export function paletteFromPlan(plan: EditorPlan): string[] {
    const colors = new Set<string>();

    for (const block of plan.blocks) {
        if (block.color) {
            colors.add(block.color);
        }

        for (const seat of block.seats) {
            if (seat.color) {
                colors.add(seat.color);
            }
        }
    }

    if (colors.size < 3) {
        colors.add('#6796ff');
        colors.add('#56aa45');
        colors.add('#f59e0b');
    }

    return Array.from(colors);
}

/**
 * Fire a two-stage confetti burst in the palette of the plan. Skips the
 * animation when the user has opted out of motion.
 */
export function celebrateSave(plan: EditorPlan): void {
    if (typeof window === 'undefined') {
        return;
    }

    if (window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches) {
        return;
    }

    const colors = paletteFromPlan(plan);

    confetti({
        particleCount: 80,
        spread: 70,
        origin: { y: 0.8 },
        colors,
        scalar: 0.9,
    });

    window.setTimeout(() => {
        confetti({
            particleCount: 50,
            spread: 100,
            origin: { y: 0.8 },
            colors,
            scalar: 0.8,
        });
    }, 120);
}
