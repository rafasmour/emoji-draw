import { usePage } from '@inertiajs/react';
import { Room } from '@/types';
import { useEffect, useState } from 'react';
import { RoomUsers } from '@/components/room/room-users';
import { RoomChat } from '@/components/room/room-chat';
import { RoomCanvas } from '@/components/room/room-canvas';
import { configureEcho, useEcho } from '@laravel/echo-react';

configureEcho({
    broadcaster: 'reverb',
    wssPort: 443,
});

export default function Game() {
    const props = usePage().props;
    const defaultRoom: Room = props.room as Room;
    const currentUser = props.auth.user;
    const [room, setRoom] = useState<Room>(defaultRoom);
    const [owner, setOwner] = useState<string>(room.owner);
    const [artist, setArtist] = useState<string>(room.artist);
    const [term, setTerm] = useState<string>(room.status['term']);
    const { listen: listenChangeOwner } = useEcho(
        `room.${room.id}`,
        'ChangeOwner',
        (e) => setOwner(e.new_owner_id),
    );
    console.log(room.canvasStrokes);
    useEffect(() => {
        listenChangeOwner();
    }, []);
    const users = room.users;
    return (
        <div
            className={
                'grid h-screen max-h-screen grid-cols-10 grid-rows-5 gap-5 p-10'
            }
        >
            <RoomCanvas
                roomId={room.id}
                term={term}
                defaultStrokes={room.canvasStrokes}
                isArtist={artist === props.auth.user.id}
                className={
                    'col-span-7 row-span-5 flex flex-col gap-4 border border-accent'
                }
            />
            <RoomUsers
                roomId={room.id}
                defaultUsers={users}
                owner={owner}
                currentUserId={currentUser.id}
                className={
                    'col-span-3 row-span-2 flex flex-col gap-4 border border-accent'
                }
            />
            <RoomChat
                roomId={room.id}
                defaultChat={room.chat}
                className={`col-span-3 row-span-3 h-full w-full`}
                guess
            />
        </div>
    );
}
