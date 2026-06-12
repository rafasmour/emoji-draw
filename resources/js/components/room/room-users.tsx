import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useSocket } from '@/connection/echo';
import { cn } from '@/lib/utils';
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
    const [scoreFlashes, setScoreFlashes] = useState<Record<string, number>>(
        {},
    );
    const scoreFlashTimers = useRef<
        Record<string, ReturnType<typeof setTimeout>>
    >({});
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
                toast.error(e.message ?? 'You were kicked from the room.');
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

            const guesserName =
                users.find((u) => u.id === e.user_id)?.name ?? 'Someone';
            const artistName =
                users.find((u) => u.id === e.artist_id)?.name ?? 'Artist';
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
        setUsers(defaultUsers);
    }, [defaultUsers]);

    useEffect(() => {
        return () => {
            Object.values(scoreFlashTimers.current).forEach((timer) =>
                clearTimeout(timer),
            );
        };
    }, []);

    return (
        <Card className={cn('min-h-fit py-0', className)}>
            <CardHeader className="border-b border-border px-5 py-5 sm:px-6">
                <CardTitle className="text-lg">Players</CardTitle>
                <CardDescription>
                    {users.length} in room. Scores update in real time.
                </CardDescription>
            </CardHeader>
            <CardContent className="flex min-h-0 flex-1 flex-col px-0">
                {users.map((user, index) => {
                    const initials = user.name
                        .split(' ')
                        .map((part) => part[0])
                        .join('')
                        .slice(0, 2)
                        .toUpperCase();

                    return (
                        <div key={`user-${user.id}-${index}`}>
                            <div className="flex items-start gap-3 px-5 py-4 sm:px-6">
                                <Avatar className="size-10 border border-border">
                                    <AvatarFallback>{initials}</AvatarFallback>
                                </Avatar>
                                <div className="min-w-0 flex-1 space-y-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <div className="truncate text-sm font-semibold">
                                            {user.name}
                                        </div>
                                        {currentUserId === user.id && (
                                            <Badge variant="secondary">
                                                You
                                            </Badge>
                                        )}
                                        {artist === user.id && (
                                            <Badge variant="outline">
                                                Artist
                                            </Badge>
                                        )}
                                        {owner === user.id && (
                                            <Badge>Owner</Badge>
                                        )}
                                    </div>
                                    {showScore && (
                                        <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                            <span className="font-medium text-foreground">
                                                {user.score ?? 0} pts
                                            </span>
                                            <span>
                                                {user.correct_guesses ?? 0}{' '}
                                                correct
                                            </span>
                                            {scoreFlashes[user.id] !==
                                                undefined && (
                                                <span className="animate-bounce text-xs font-semibold text-primary">
                                                    +{scoreFlashes[user.id]}
                                                </span>
                                            )}
                                        </div>
                                    )}
                                </div>
                                {isOwner && user.id !== currentUserId && (
                                    <div className="flex items-center gap-2">
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    onClick={() =>
                                                        changeOwner(
                                                            roomId,
                                                            user.id,
                                                        )
                                                    }
                                                >
                                                    <CrownIcon />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                Transfer room ownership
                                            </TooltipContent>
                                        </Tooltip>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    onClick={() =>
                                                        kickPlayer(
                                                            roomId,
                                                            user.id,
                                                        )
                                                    }
                                                >
                                                    <CircleX size={12} />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                Kick player
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                )}
                            </div>
                            {index < users.length - 1 && <Separator />}
                        </div>
                    );
                })}
            </CardContent>
        </Card>
    );
}
