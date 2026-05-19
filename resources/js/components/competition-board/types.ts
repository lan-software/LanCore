export interface StageScheduleDto {
    id: string;
    lanbrackets_stage_id: string;
    stage_name: string;
    stage_type: string;
    sequence: number;
    starts_at: string | null;
    estimated_duration_minutes: number;
    reserve_buffer_minutes: number;
    duration_overridden: boolean;
    notes: string | null;
    ends_at: string | null;
    slack_minutes: number | null;
    slack_against: 'next_stage' | 'competition_end' | null;
}

export interface CompetitionDto {
    id: string;
    name: string;
    status: string | null;
    starts_at: string | null;
    ends_at: string | null;
    game_name: string | null;
    stage_schedules: StageScheduleDto[];
    total_minutes: number;
    current_slack_minutes: number | null;
}

export interface EventDto {
    id: string;
    name: string;
    start_date: string | null;
    end_date: string | null;
}

export interface ConflictDto {
    severity: 'warning' | 'error';
    message_key: string;
    params: Record<string, unknown>;
    schedule_ids: string[];
}

export interface MatchProposalDto {
    competition_id: string;
    competition_name: string;
    stage_id: string;
    stage_name: string;
    match_id: number | string;
    score: number;
    blocked: boolean;
    participants: Array<{ participant_id: number | string | null; name: string | null }>;
    reason_keys: string[];
}

export interface TimeSegment {
    start: number;
    end: number;
    pxPerMinute: number;
    classification: 'busy' | 'idle';
    pxOffset: number;
}
