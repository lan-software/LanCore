export function snapToGrid(value: number, gridSize: number): number {
    if (gridSize <= 0) {
        return Math.round(value);
    }

    return Math.round(value / gridSize) * gridSize;
}

export function rectContainsPoint(
    rect: { x1: number; y1: number; x2: number; y2: number },
    point: { x: number; y: number },
): boolean {
    const minX = Math.min(rect.x1, rect.x2);
    const maxX = Math.max(rect.x1, rect.x2);
    const minY = Math.min(rect.y1, rect.y2);
    const maxY = Math.max(rect.y1, rect.y2);

    return (
        point.x >= minX && point.x <= maxX && point.y >= minY && point.y <= maxY
    );
}

/**
 * Generate a 2D grid of seats. Returns row payloads suitable for passing to
 * SeatPlanFactory::withBlocks / the editor's AddBlockDialog NxM wizard.
 */
export function generateGridSeats(options: {
    rows: number;
    cols: number;
    pitch: number;
    rowPitch?: number;
    originX?: number;
    originY?: number;
    startRowLetter?: string;
    startSeatNumber?: number;
}): Array<{
    name: string;
    sort_order: number;
    seats: Array<{
        number: number;
        title: string;
        x: number;
        y: number;
        salable: true;
    }>;
}> {
    const {
        rows,
        cols,
        pitch,
        rowPitch = pitch,
        originX = 0,
        originY = 0,
        startRowLetter = 'A',
        startSeatNumber = 1,
    } = options;

    const rowBase = startRowLetter.charCodeAt(0);
    const rowsOut: ReturnType<typeof generateGridSeats> = [];

    for (let r = 0; r < rows; r++) {
        const rowName = String.fromCharCode(rowBase + r);
        const seats: Array<{
            number: number;
            title: string;
            x: number;
            y: number;
            salable: true;
        }> = [];

        for (let c = 0; c < cols; c++) {
            const number = startSeatNumber + c;
            seats.push({
                number,
                title: `${rowName}${number}`,
                x: originX + c * pitch,
                y: originY + r * rowPitch,
                salable: true,
            });
        }

        rowsOut.push({
            name: rowName,
            sort_order: r,
            seats,
        });
    }

    return rowsOut;
}

export function newClientId(prefix = 'new'): string {
    return `${prefix}-${Math.random().toString(36).slice(2, 10)}${Date.now().toString(36).slice(-4)}`;
}
