export type EventContext = {
    selectedEventId: number | null;
    selectedEvent: { id: number; name: string } | null;
    events: { id: number; name: string }[];
};
