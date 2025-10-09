import { router, usePage } from '@inertiajs/react';
import { Room } from '@/types';
import { Button } from '@/components/ui/button';
import { useEffect, useState } from 'react';
import Echo from 'laravel-echo';
import { useEcho } from '@laravel/echo-react';
import { lobbyEvents, useEchoLobby } from '@/events/room/useLobbyEvents';
import { destroyRoom, leaveRoom, startGame } from '@/requests/room/room';

export default function Lobby() {
    const props = usePage().props;
    const defaultRoom: Room = props.room as Room;
    const [room, setRoom] = useState<Room>(defaultRoom);
    const users = room.users;
    const { stopListening, listen} = useEchoLobby(room.id, setRoom);
    useEffect(() => {
        listen();
    }, [listen, stopListening]);

    console.log(props);
    return (
        <div className={'flex flex-col gap-4'}>
            <div className={'flex flex-col gap-4 border border-accent'}>
                {users &&
                    users.length > 0 &&
                    users?.map((user) => {
                        return (
                            <div key={`user-${user.id}`} className={'p-4'}>
                                {user.name}{' '}
                                {room.owner === user.id && '(Owner) 👑'}
                            </div>
                        );
                    })}
            </div>
            <div className={'flex flex-wrap gap-4'}>
                <Button onClick={() => leaveRoom(room.id)}>Leave</Button>
                <Button onClick={() => destroyRoom(room.id)}>Destroy Room</Button>
                <Button onClick={() => startGame(room.id)}>Start Game</Button>
            </div>
        </div>
    );
}
