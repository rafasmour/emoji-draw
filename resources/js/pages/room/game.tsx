import { RoomCanvas } from '@/components/room/room-canvas';
import { RoomChat } from '@/components/room/room-chat';
import { RoomUsers } from '@/components/room/room-users';
import { useSocket } from '@/connection/echo';
import { useRoomLeave } from '@/hooks/use-room-leave';
import { dashboard } from '@/routes';
import { Room, SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { configureEcho } from '@laravel/echo-react';
import { useEffect, useState } from 'react';
import { ToastContainer, toast } from 'react-toastify';

configureEcho({
    broadcaster: 'reverb',
    wssPort: 443,
});

export default function Game() {
    const props = usePage<SharedData & { room: Room }>().props;
    const defaultRoom: Room = props.room as Room;
    const currentUser = props.auth.user!;
    const currentUserId = String(currentUser.id);
    const [room] = useState<Room>(defaultRoom);
    const [owner, setOwner] = useState<string>(room.owner);
    const [artist, setArtist] = useState<string>(room.artist);
    const [isArtist, setIsArtist] = useState<boolean>(
        room.artist === currentUserId,
    );
    useRoomLeave(room.id);

    useEffect(() => {
        setIsArtist(() => artist === currentUserId);
    }, [artist, currentUserId]);

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
        () => router.visit(`/room/${room.id}/results`),
    );
    const { listen: listenRoomDestroyed } = useSocket(
        `room.${room.id}`,
        'RoomDestroyed',
        () => {
            toast.error('Room was destroyed.');
            setTimeout(() => {
                window.location.href = dashboard().url;
            }, 1200);
        },
    );

    useEffect(() => {
        listenChangeOwner();
        listenStartRound();
        listenRevealHint();
        listenGameOver();
        listenRoomDestroyed();
    }, [
        listenChangeOwner,
        listenGameOver,
        listenRevealHint,
        listenRoomDestroyed,
        listenStartRound,
    ]);

    const users = room.users;

    return (
        <>
            <ToastContainer position="bottom-right" />
            <main className="grid min-h-dvh gap-4 p-3 sm:gap-5 sm:p-5 xl:h-dvh xl:grid-cols-10 xl:grid-rows-5 xl:overflow-hidden xl:p-6">
                <RoomCanvas
                    roomId={room.id}
                    term={term}
                    hint={hint}
                    defaultStrokes={room.canvas}
                    isArtist={isArtist}
                    timeLeft={room.status.time}
                    roundDuration={room.settings.timeLimit}
                    className="min-h-0 xl:col-span-7 xl:row-span-5"
                />
                <RoomUsers
                    roomId={room.id}
                    defaultUsers={users}
                    owner={owner}
                    artist={artist}
                    currentUserId={currentUserId}
                    className="min-h-0 xl:col-span-3 xl:row-span-2"
                />
                <RoomChat
                    roomId={room.id}
                    defaultChat={room.chat}
                    guess
                    showForm={false}
                    className="min-h-80 xl:col-span-3 xl:row-span-3 xl:min-h-0"
                />
            </main>
        </>
    );
}
