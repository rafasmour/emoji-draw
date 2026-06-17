import { Button } from '@/components/ui/button';
import { useSocket } from '@/connection/echo';
import { changeOwner, kickPlayer } from '@/requests/room/room';
import { Room } from '@/types';
import { configureEcho } from '@laravel/echo-react';
import { CircleX, CrownIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'react-toastify';

export interface RoomUsersProps {
    roomId: string;
    defaultUsers: Room['users'];
    className?: string;
    owner: string;
    artist?: string;
    currentUserId: string;
    showScore?: boolean;
}
configureEcho({
    broadcaster: 'reverb',
    wssPort: 443,
});
export function RoomUsers({
    roomId,
    defaultUsers,
    owner,
    className,
    currentUserId,
    artist,
    showScore = true,
}: RoomUsersProps) {
    const [users, setUsers] = useState<Room['users']>(defaultUsers);
    const [scoreFlashes, setScoreFlashes] = useState<Record<string, number>>({});
    const scoreFlashTimers = useRef<Record<string, ReturnType<typeof setTimeout>>>({});
    const isOwner = owner === currentUserId;

    const { listen: listenJoin } = useSocket(`room.${roomId}`, 'Join', (e) => {
        setUsers((prev) => [...prev, e.user]);
    });
    const { listen: listenLeave } = useSocket(
        `room.${roomId}`,
        'Leave',
        (e) => {
            setUsers((prev) => prev.filter((user) => user.id !== e.user_id));
        },
    );
    const { listen: listenKick } = useSocket(
        `room.${roomId}`,
        'PlayerKicked',
        (e) => {
            if (e.user_id === currentUserId) {
                toast.error(e.message ?? "You were kicked from the room.");
                window.location.reload();
            }
            setUsers((prev) => prev.filter((user) => user.id !== e.user_id));
        },
    );
    const { listen: listenCorrectGuess } = useSocket(
        `room.${roomId}`,
        'CorrectGuess',
        (e) => {
            if (e.guesser_score === 0) return;

            setUsers((prev) =>
                prev.map((u) => {
                    const updated = e.users.find((eu) => eu.id === u.id);
                    return updated ? { ...u, score: updated.score } : u;
                }),
            );

            const guesserName = users.find((u) => u.id === e.user_id)?.name ?? 'Someone';
            const artistName = users.find((u) => u.id === e.artist_id)?.name ?? 'Artist';
            const firstBonus = e.is_first_guess ? ' (first guess!)' : '';
            toast.success(`+${e.guesser_score} — ${guesserName}${firstBonus}`);
            toast.info(`+${e.artist_score} — ${artistName} (artist)`);

            const flashEntry = (userId: string, points: number) => {
                if (scoreFlashTimers.current[userId]) {
                    clearTimeout(scoreFlashTimers.current[userId]);
                }
                setScoreFlashes((prev) => ({ ...prev, [userId]: points }));
                scoreFlashTimers.current[userId] = setTimeout(() => {
                    setScoreFlashes((prev) => {
                        const next = { ...prev };
                        delete next[userId];
                        return next;
                    });
                }, 2000);
            };

            flashEntry(e.user_id, e.guesser_score);
            flashEntry(e.artist_id, e.artist_score);
        },
    );
    useEffect(() => {
        listenJoin();
        listenLeave();
        listenKick();
        listenCorrectGuess();
    }, [listenJoin, listenLeave, listenKick, listenCorrectGuess]);
    useEffect(() => {
        setUsers(users);
    });
    return (
        <div className={className}>
            {users &&
                users.length > 0 &&
                users?.map((user, index) => {
                    return (
                        <div
                            key={`user-${user.id}-${index}`}
                            className={'flex w-full flex-row items-center gap-4 p-4'}
                        >
                            <div className="flex flex-1 flex-col">
                                <div
                                    className={`${currentUserId === user.id ? 'text-yellow-500' : ''}`}
                                >
                                    {user.name}{' '}
                                    {artist === user.id && '(Artist) 🖌️'}
                                    {owner === user.id && '(Owner) 👑'}
                                </div>
                                {showScore && (
                                    <div className="flex items-center gap-1 text-sm font-semibold text-muted-foreground">
                                        <span>{user.score ?? 0} pts</span>
                                        {scoreFlashes[user.id] !== undefined && (
                                            <span className="animate-bounce text-xs text-green-500">
                                                +{scoreFlashes[user.id]}
                                            </span>
                                        )}
                                    </div>
                                )}
                            </div>

                            {isOwner && user.id !== currentUserId && (
                                <div className="flex flex-row gap-2">
                                    <Button
                                        onClick={() =>
                                            changeOwner(roomId, user.id)
                                        }
                                    >
                                        <CrownIcon />
                                    </Button>
                                    <Button
                                        onClick={() =>
                                            kickPlayer(roomId, user.id)
                                        }
                                    >
                                        <CircleX size={12} />
                                    </Button>
                                </div>
                            )}
                        </div>
                    );
                })}
        </div>
    );
}
