import { RoomChat } from '@/components/room/room-chat';
import { RoomUsers } from '@/components/room/room-users';
import { Button } from '@/components/ui/button';
import { destroyRoom, leaveRoom, startGame } from '@/requests/room/room';
import { Room } from '@/types';
import { usePage } from '@inertiajs/react';
import { configureEcho, useEcho } from '@laravel/echo-react';
import { useEffect, useState } from 'react';

configureEcho({
    broadcaster: 'reverb',
    wssPort: 443,
});
export default function Lobby() {
    const props = usePage().props;
    const defaultRoom: Room = props.room as Room;
    const currentUser = props.auth.user;
    const [room, setRoom] = useState<Room>(defaultRoom);
    const users = room.users;
    const { listen: listenChangeOwner } = useEcho(
        `room.${room.id}`,
        'ChangeOwner',
        (e) => {
            console.log(e);
            setRoom((prev) => ({
                ...prev,
                owner: e.new_owner_id,
            }));
        },
    );
    const { listen: listenStart } = useEcho(
        `room.${room.id}`,
        'GameStarted',
        () => {
            window.location.href = `/room/${room.id}/game`;
        },
    );
    const { listen: listenRoomDestroyed } = useEcho(
        `room.${room.id}`,
        'RoomDestroyed',
        () => {
            alert('room has been destroyed');
            window.location.href = '/room';
        },
    );
    useEffect(() => {
        listenStart();
        listenChangeOwner();
        listenRoomDestroyed();
    });

    useEffect(() => {
        const owner = room.users.find((user) => user.id === room.owner);
        console.log(owner?.name);
    }, [room]);

    return (
        <div
            className={
                'grid h-screen max-h-screen grid-cols-10 grid-rows-5 gap-5 p-10'
            }
        >
            <RoomUsers
                roomId={room.id}
                defaultUsers={users}
                owner={room.owner}
                currentUserId={currentUser.id}
                className={
                    'col-span-7 row-span-5 flex flex-col gap-4 border border-accent'
                }
            />
            <div className={'col-span-3 row-span-2 flex flex-col gap-5'}>
                <Button onClick={() => leaveRoom(room.id)}>Leave</Button>
                <Button onClick={() => destroyRoom(room.id)}>
                    Destroy Room
                </Button>
                <Button onClick={() => startGame(room.id)}>Start Game</Button>
            </div>
            <RoomChat
                roomId={room.id}
                defaultChat={room.chat}
                className={`col-span-3 row-span-3 h-full w-full`}
            />
        </div>
    );
}
