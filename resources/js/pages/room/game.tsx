import {
    GUESS_BURST_DURATION_MS,
    GuessBurst,
} from '@/components/room/guess-burst';
import { RoomCanvas } from '@/components/room/room-canvas';
import { RoomChat } from '@/components/room/room-chat';
import { RoomUsers } from '@/components/room/room-users';
import { RoundChangeOverlay } from '@/components/room/round-change-overlay';
import { useSocket } from '@/connection/echo';
import { useRoomConnectionLease } from '@/hooks/use-room-connection-lease';
import { useRoomLeave } from '@/hooks/use-room-leave';
import { rooms } from '@/routes/room/';
import { Room, SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { ToastContainer, toast } from 'react-toastify';

export default function Game() {
    const props = usePage<SharedData & { room: Room; is_artist: boolean }>()
        .props;
    const defaultRoom: Room = props.room as Room;
    const currentUser = props.auth.user!;
    const currentUserId = String(currentUser.id);
    const [room] = useState<Room>(defaultRoom);
    const [owner, setOwner] = useState<string>(room.owner);
    const [artist, setArtist] = useState<string>(room.artist);
    const [isArtist, setIsArtist] = useState<boolean>(props.is_artist);
    const currentRound = useRef<number>(room.status.round);
    const assignedTerm = useRef<{ round: number; term: string } | null>(
        room.status.term === null
            ? null
            : { round: room.status.round, term: room.status.term },
    );
    useRoomLeave(room.id);
    useRoomConnectionLease(
        room.id,
        props.roomConnection.heartbeatIntervalSeconds,
    );

    useEffect(() => {
        setIsArtist(() => artist === currentUserId);
    }, [artist, currentUserId]);

    const [term, setTerm] = useState<string>(room.status.term ?? '');
    const [hint, setHint] = useState<string>('');
    const [roundChange, setRoundChange] = useState<{
        round: number;
        previousTerm: string;
        artistId: string;
    } | null>(null);
    const [burst, setBurst] = useState<{ points: number; key: number } | null>(
        null,
    );
    const dismissRoundChange = useCallback(() => setRoundChange(null), []);
    const { listen: listenChangeOwner } = useSocket(
        `room.${room.id}`,
        'ChangeOwner',
        (e) => setOwner(e.new_owner_id),
    );
    const { listen: listenStartRound } = useSocket(
        `room.${room.id}`,
        'StartRound',
        (e) => {
            currentRound.current = e.round;
            setTerm(
                assignedTerm.current?.round === e.round
                    ? assignedTerm.current.term
                    : '',
            );
            setArtist(e.artist_id);
            setHint(e.initial_hint);
            if (e.previous_term) {
                setRoundChange({
                    round: e.round,
                    previousTerm: e.previous_term,
                    artistId: e.artist_id,
                });
            }
        },
    );
    const { listen: listenCorrectGuess } = useSocket(
        `room.${room.id}`,
        'CorrectGuess',
        (e) => {
            if (e.user_id !== currentUserId) return;

            setBurst({ points: e.guesser_score, key: Date.now() });
        },
    );
    const { listen: listenArtistTermAssigned } = useSocket(
        `room.${room.id}.user.${currentUserId}`,
        'ArtistTermAssigned',
        (e) => {
            if (e.round < currentRound.current) {
                return;
            }

            assignedTerm.current = { round: e.round, term: e.term };
            setTerm(e.term);
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
    const { listen: listenStopGame } = useSocket(
        `room.${room.id}`,
        'StopGame',
        () => router.visit(`/room/${room.id}`),
    );
    const { listen: listenRoomDestroyed } = useSocket(
        `room.${room.id}`,
        'RoomDestroyed',
        () => {
            toast.error('Room was destroyed.');
            setTimeout(() => {
                window.location.href = rooms().url;
            }, 1200);
        },
    );

    useEffect(() => {
        listenChangeOwner();
        listenStartRound();
        listenCorrectGuess();
        listenArtistTermAssigned();
        listenRevealHint();
        listenGameOver();
        listenStopGame();
        listenRoomDestroyed();
    }, [
        listenChangeOwner,
        listenArtistTermAssigned,
        listenCorrectGuess,
        listenGameOver,
        listenRevealHint,
        listenRoomDestroyed,
        listenStartRound,
        listenStopGame,
    ]);

    useEffect(() => {
        if (!burst) return;

        const timer = setTimeout(() => setBurst(null), GUESS_BURST_DURATION_MS);
        return () => clearTimeout(timer);
    }, [burst]);

    const users = room.users;

    return (
        <>
            <ToastContainer position="bottom-right" />
            {burst && <GuessBurst key={burst.key} points={burst.points} />}
            {roundChange && (
                <RoundChangeOverlay
                    round={roundChange.round}
                    totalRounds={room.settings.rounds}
                    previousTerm={roundChange.previousTerm}
                    artistName={
                        users.find((u) => u.id === roundChange.artistId)
                            ?.name ?? 'The next artist'
                    }
                    isArtist={roundChange.artistId === currentUserId}
                    onDismiss={dismissRoundChange}
                />
            )}
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
