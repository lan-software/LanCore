export type EventContext = {
    selectedEventId: string | null;
    selectedEvent: { id: string; name: string } | null;
    events: { id: string; name: string }[];
};
