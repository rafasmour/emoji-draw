import { RoomChat } from '@/components/room/room-chat';
import { RoomUsers } from '@/components/room/room-users';
import { Button } from '@/components/ui/button';
import { useSocket } from '@/connection/echo';
import { useRoomConnectionLease } from '@/hooks/use-room-connection-lease';
import { useRoomLeave } from '@/hooks/use-room-leave';
import { destroyRoom, leaveRoom, startGame } from '@/requests/room/room';
import { rooms as roomIndex } from '@/routes/room';
import { Room, SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { configureEcho } from '@laravel/echo-react';
import { useEffect, useState } from 'react';
import { ToastContainer, toast } from 'react-toastify';
export default function Lobby() {
    const props = usePage<SharedData & { room: Room }>().props;
    const defaultRoom: Room = props.room as Room;
    const currentUser = props.auth.user!;
    const [room, setRoom] = useState<Room>(defaultRoom);
    const users = room.users;
    useRoomLeave(room.id);
    useRoomConnectionLease(room.id, props.roomConnection.heartbeatIntervalSeconds);
    const { listen: listenChangeOwner } = useSocket(
        `room.${room.id}`,
        'ChangeOwner',
        (e) => {
            setRoom((prev) => ({
                ...prev,
                owner: e.new_owner_id,
            }));
        },
    );
    const { listen: listenStart } = useSocket(
        `room.${room.id}`,
        'StartGame',
        () => {
            window.location.href = `/room/${room.id}/game`;
        },
    );
    const { listen: listenRoomDestroyed } = useSocket(
        `room.${room.id}`,
        'RoomDestroyed',
        () => {
            toast.error('Room was destroyed.');
            setTimeout(() => {
                window.location.href = roomIndex().url;
            }, 1200);
        },
    );
    useEffect(() => {
        listenStart();
        listenChangeOwner();
        listenRoomDestroyed();
    });

    return (
        <>
            <ToastContainer position="bottom-right" />
            <div
                className={
                    'flex flex-col md:grid min-h-screen h-screen md:max-h-screen grid-rows-[3fr_1fr_5ft]  md:grid-cols-10 md:grid-rows-5 gap-5 p-10'

                }
            >
            <RoomUsers
                roomId={room.id}
                defaultUsers={users}
                owner={room.owner}
                currentUserId={String(currentUser.id)}
                showScore={false}
                className={
                    'md:col-span-7 row-span-5 min-h-[40rem] md:min-h-full flex flex-col gap-4 border border-accent'
                }
            />
            <div className={'md:col-span-3 md:row-span-2 flex flex-col gap-5'}>
                <Button onClick={() => leaveRoom(room.id)}>Leave</Button>
                <Button onClick={() => destroyRoom(room.id)}>
                    Destroy Room
                </Button>
                <Button onClick={() => startGame(room.id)}>Start Game</Button>
            </div>
            <RoomChat
                roomId={room.id}
                defaultChat={room.chat}
                className={`md:col-span-3 md:row-span-3 h-full min-h-70 w-full`}
            />
            </div>
        </>
    );
}
