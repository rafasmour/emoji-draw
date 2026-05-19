import { RoomCanvas } from '@/components/room/room-canvas';
import { RoomChat } from '@/components/room/room-chat';
import { RoomUsers } from '@/components/room/room-users';
import { useSocket } from '@/connection/echo';
import { useRoomLeave } from '@/hooks/use-room-leave';
import { Room } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { configureEcho } from '@laravel/echo-react';
import { useEffect, useState } from 'react';
import { ToastContainer } from 'react-toastify';

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
    const [isArtist, setIsArtist] = useState();
    useRoomLeave(room.id);
    useEffect(() => {
        setIsArtist(() => artist === props.auth.user.id);
    }, [artist]);
    const [term, setTerm] = useState<string>(room.status.term);
    const [hint, setHint] = useState<string>('');
    const { listen: listenChangeOwner } = useSocket(
        `room.${room.id}`,
        'ChangeOwner',
        (e) => setOwner(e.new_owner_id),
    );
    const { listen: listenStartRound } = useSocket(
        `room.${room.id}`,
        'StartRound',
        (e) => {
            setTerm(e.term ?? '');
            setArtist(e.artist_id);
            setHint(e.initial_hint);
        },
    );
    const { listen: listenRevealHint } = useSocket(
        `room.${room.id}`,
        'RevealHint',
        (e) => {
            if (!isArtist) setHint(e.hint);
        },
    );
    const { listen: listenGameOver } = useSocket(
        `room.${room.id}`,
        'GameOver',
        () => router.visit(`/room/${room.id}`),
    );
    useEffect(() => {
        listenChangeOwner();
        listenStartRound();
        listenRevealHint();
        listenGameOver();
    }, []);
    const users = room.users;
    return (
        <>
            <ToastContainer position="bottom-right" />
            <div
                className={
                    'grid h-screen max-h-screen grid-cols-10 grid-rows-5 gap-5 p-10'
                }
            >
            <RoomCanvas
                roomId={room.id}
                term={term}
                hint={hint}
                defaultStrokes={room.canvas}
                isArtist={isArtist}
                timeLeft={room.status.time}
                roundDuration={room.settings.timeLimit}
                className={
                    'col-span-7 row-span-5 flex flex-col gap-4 border border-accent'
                }
            />
            <RoomUsers
                roomId={room.id}
                defaultUsers={users}
                owner={owner}
                artist={artist}
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
        </>
    );
}
