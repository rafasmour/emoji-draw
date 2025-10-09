import { setRoomState } from '@/types';
import { configureEcho, useEcho } from '@laravel/echo-react';

configureEcho({
    broadcaster: 'reverb',
    wssPort: 443,
});

export const lobbyEvents: {
    [event: string]: (setRoom: setRoomState, data: object) => void;
} = {
    ChangeOwner: () => {},
    ChatMessage: () => {},
    ClearChat: () => {},
    Join: () => {},
    Leave: () => {},
    StartGame: () => {},
    RoomEvents: () => {},
    RoomPublicChanged: () => {},
};

const lobbyEventNames = Object.keys(lobbyEvents);

export const useEchoLobby = (roomId: string, setRoom: setRoomState) => {
    return useEcho(`room.${roomId}`, lobbyEventNames, (payload) => {
        console.log(payload);
        lobbyEvents[e]?.(setRoom, data);
    }, [roomId], );
};
