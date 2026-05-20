import { User } from '@/types';

export interface RoomUserPayload {
    id: string;
    name: string;
    score: number;
    guesses: number;
    correct_guesses: number;
    guessed: boolean;
    room_token: string | null;
}

export interface EventPayloads {
    CanvasStroke: {
        stroke: { x: number; y: number; size: number; emoji: string };
    };
    ChangeOwner: {
        event: 'ChangeOwner';
        new_owner_id: string;
    };
    ChatMessage: {
        event: 'ChatMessage';
        message: { user_id: string; user: string; message: string };
    };
    ClearCanvas: Record<string, never>;
    ClearChat: {
        event: 'ClearChat';
    };
    CorrectGuess: {
        event: 'CorrectGuess';
    };
    FinishGame: {
        message: string;
    };
    GameOver: {
        event: 'GameOver';
    };
    Join: {
        event: 'Join';
        user: RoomUserPayload;
    };
    Leave: {
        event: 'Leave';
        user_id: string;
    };
    OwnerLeave: {
        event: 'OwnerLeave';
        user: Pick<User, 'id' | 'name' | 'email'>;
    };
    PlayerKicked: {
        event: 'PlayerKicked';
        user_id: string;
        message: string;
    };
    RoomDestroyed: {
        event: 'RoomDestroyed';
    };
    RoomPublicChanged: {
        event: 'RoomPublicChanged';
        public: boolean;
        message: string;
    };
    StartGame: {
        event: 'StartGame';
        message: string;
        room_id: string;
    };
    RevealHint: {
        event: 'RevealHint';
        hint: string;
        round: number;
    };
    StartRound: {
        event: 'StartRound';
        term: string;
        artist_id: string;
        time: string;
        initial_hint: string;
    };
    StopGame: {
        event: 'StopGame';
    };
    StopRound: {
        event: 'StopRound';
    };
}

export type EventName = keyof EventPayloads;
export type EventPayload<E extends EventName> = EventPayloads[E];
