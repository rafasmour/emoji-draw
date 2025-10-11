import { Room, setRoomState } from '@/types';
import { configureEcho, useEcho } from '@laravel/echo-react';

configureEcho({
    broadcaster: 'reverb',
    wssPort: 443,
});

export const lobbyEvents: {
    [event: string]: (
        setRoom: setRoomState,
        data: { [key: string]: any },
        userId?: string,
    ) => void;
} = {
    ChangeOwner: (setRoom, data) => {
        const { new_owner_id } = data;
        if (!new_owner_id) return;
        setRoom((prevRoom) => {
            return {
                ...prevRoom,
                owner: new_owner_id,
            };
        });
    },
    ChatMessage: (setRoom, data) => {
        const { message } = data;
        if (!message) return;
        setRoom((prevRoom) => {
            return {
                ...prevRoom,
                chat: [...prevRoom.chat, message as Room['chat'][number]],
            };
        });
    },
    ClearChat: (setRoom, data) => {
        setRoom((prevRoom) => {
            return {
                ...prevRoom,
                chat: [],
            };
        });
    },
    Join: (setRoom, data) => {
        console.log(data);
        const { user, message } = data;
        if (!user || !message) return;
        setRoom((prevRoom) => {
            const users = [...prevRoom.users, user];
            const chat = [...prevRoom.chat, message];
            return {
                ...prevRoom,
                users,
                chat,
            } as Room;
        });
    },
    Leave: (setRoom, data) => {
        const { user_id, message } = data;
        if (!user_id || !message) return;
        setRoom((prevRoom) => {
            const users = prevRoom.users.filter((user) => user.id !== user_id);
            const chat = [...prevRoom.chat, message];
            return {
                ...prevRoom,
                users,
            };
        });
    },
    StartGame: (setRoom, data) => {
        setRoom((prevRoom) => {
            return {
                ...prevRoom,
                started: true,
            };
        });
    },
    RoomEvents: () => {},
    RoomPublicChanged: () => {},
    PlayerKicked: (setRoom, data, userId) => {
        console.log(data.user_id);
        if (!data.user_id) return;
        if (userId === data.user_id) {
            window.location.href = '/room';
            alert('You have been kicked from the room.');
            return;
        }
        setRoom((prevRoom) => {
            const { user_id } = data;
            return {
                ...prevRoom,
                users: prevRoom.users.filter((user) => user.id !== user_id),
            } as Room;
        });
    },
};

const lobbyEventNames = Object.keys(lobbyEvents);

export const useEchoLobby = (
    roomId: string,
    setRoom: setRoomState,
    userId: string,
) => {
    return useEcho(
        `room.${roomId}`,
        lobbyEventNames,
        (payload) => {
            console.log(payload.event);
            lobbyEvents[payload.event]?.(setRoom, payload, userId);
        },
        [roomId],
    );
};
