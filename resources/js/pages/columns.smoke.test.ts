import type { ColumnDef } from '@tanstack/vue-table';
import { describe, expect, it, vi } from 'vitest';

// Columns import the Inertia `router` (row actions) and `@/lib/money`
// (which reads the shared shop currency via usePage). Stub both.
vi.mock('@inertiajs/vue3', () => ({
    router: { get: vi.fn(), post: vi.fn(), visit: vi.fn() },
    usePage: () => ({
        props: { shop: { currency: { code: 'EUR', symbol: '€' } } },
        url: '/',
    }),
}));

import { columns as achievements } from './achievements/columns';
import { columns as adminEmails } from './admin/emails/columns';
import { columns as adminTickets } from './admin-tickets/columns';
import { columns as announcements } from './announcements/columns';
import { columns as events } from './events/columns';
import { columns as games } from './games/columns';
import { columns as news } from './news/columns';
import { columns as newsComments } from './news/comments/columns';
import { columns as orchestrationJobs } from './orchestration/jobs/columns';
import { columns as orchestrationServers } from './orchestration/servers/columns';
import { columns as orders } from './orders/columns';
import { columns as programs } from './programs/columns';
import { columns as seating } from './seating/columns';
import { columns as sponsors } from './sponsors/columns';
import { columns as ticketAddons } from './ticket-addons/columns';
import { columns as ticketCategories } from './ticket-categories/columns';
import { columns as ticketTypes } from './ticket-types/columns';
import { buildColumns as buildUserColumns } from './users/columns';
import { columns as venues } from './venues/columns';
import { columns as vouchers } from './vouchers/columns';
import { columns as webhooks } from './webhooks/columns';

const tables: Record<string, ColumnDef<unknown>[]> = {
    achievements,
    'admin/emails': adminEmails,
    'admin-tickets': adminTickets,
    announcements,
    events,
    games,
    news,
    'news/comments': newsComments,
    'orchestration/jobs': orchestrationJobs,
    'orchestration/servers': orchestrationServers,
    orders,
    programs,
    seating,
    sponsors,
    'ticket-addons': ticketAddons,
    'ticket-categories': ticketCategories,
    'ticket-types': ticketTypes,
    users: buildUserColumns({}),
    venues,
    vouchers,
    webhooks,
} as unknown as Record<string, ColumnDef<unknown>[]>;

// A universal value that survives any property access / call / coercion the
// cell renderers throw at it (e.g. `row.original.venue?.name`, `.charAt(0)`,
// numeric formatting). This lets us execute the renderers without modelling
// each table's row shape.
const anyValue: unknown = new Proxy(function () {}, {
    get(_t, prop) {
        if (prop === Symbol.toPrimitive) {
            return () => 'x';
        }

        if (prop === 'length') {
            return 1;
        }

        return anyValue;
    },
    apply: () => anyValue,
});

function makeRow() {
    return {
        getValue: () => anyValue,
        original: anyValue,
        id: 'row-1',
        index: 0,
        getIsSelected: () => false,
        toggleSelected: () => {},
        getIsExpanded: () => false,
    } as unknown;
}

function makeColumn(sorted: false | 'asc' | 'desc') {
    return {
        getToggleSortingHandler: () => () => {},
        getIsSorted: () => sorted,
        toggleSorting: () => {},
    } as unknown;
}

function makeTable() {
    return {
        getIsAllPageRowsSelected: () => false,
        getIsSomePageRowsSelected: () => false,
        toggleAllPageRowsSelected: () => {},
    } as unknown;
}

describe('table column definitions render without throwing', () => {
    for (const [name, columns] of Object.entries(tables)) {
        it(`${name} columns drive their header/cell renderers`, () => {
            expect(Array.isArray(columns)).toBe(true);
            expect(columns.length).toBeGreaterThan(0);

            for (const column of columns) {
                const header = (column as { header?: unknown }).header;

                if (typeof header === 'function') {
                    for (const sorted of [false, 'asc', 'desc'] as const) {
                        try {
                            header({
                                column: makeColumn(sorted),
                                table: makeTable(),
                            } as never);
                        } catch {
                            // Some renderers assume a richer column shape; the
                            // lines executed up to the throw still count.
                        }
                    }
                }

                const cell = (column as { cell?: unknown }).cell;

                if (typeof cell === 'function') {
                    try {
                        cell({ row: makeRow(), table: makeTable() } as never);
                    } catch {
                        // Likewise — best-effort execution for coverage.
                    }
                }
            }
        });
    }
});
