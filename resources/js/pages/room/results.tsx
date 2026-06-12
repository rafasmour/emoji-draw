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
import { cn } from '@/lib/utils';
import { leaveRoom } from '@/requests/room/room';
import { Room, SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';

interface ResultsRoom {
    id: string;
    name: string;
    owner: string;
    users: Room['users'];
}

interface PodiumSlot {
    rank: number;
    label: string;
    pedestalHeight: string;
}

const SLOTS: PodiumSlot[] = [
    { rank: 1, label: 'Second place', pedestalHeight: 'min-h-28' },
    { rank: 0, label: 'First place', pedestalHeight: 'min-h-40' },
    { rank: 2, label: 'Third place', pedestalHeight: 'min-h-24' },
];

export default function Results() {
    const props = usePage<SharedData & { room: ResultsRoom }>().props;
    const room = props.room as ResultsRoom;
    const currentUser = props.auth.user;
    const currentUserId = currentUser ? String(currentUser.id) : null;

    const sorted = [...room.users].sort((a, b) => b.score - a.score);
    const currentUserIndex = currentUser
        ? sorted.findIndex((user) => user.id === currentUserId)
        : -1;

    return (
        <div className="min-h-screen bg-background p-4 sm:p-6 lg:p-8">
            <div className="mx-auto flex min-h-[calc(100vh-2rem)] max-w-6xl flex-col justify-center gap-6">
                <Card className="py-0">
                    <CardHeader className="gap-4 border-b border-border px-5 py-5 sm:px-6 lg:flex-row lg:items-end lg:justify-between">
                        <div className="space-y-1">
                            <CardTitle className="text-3xl">
                                {room.name}
                            </CardTitle>
                            <CardDescription>
                                Final standings for this match.
                            </CardDescription>
                        </div>
                        {currentUser && currentUserIndex >= 0 && (
                            <Badge
                                variant="secondary"
                                className="self-start lg:self-auto"
                            >
                                You finished in position #{currentUserIndex + 1}
                            </Badge>
                        )}
                    </CardHeader>
                    <CardContent className="grid gap-6 px-5 py-6 sm:px-6">
                        <div className="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
                            <div className="rounded-xl border border-border bg-muted/20 p-4 sm:p-6">
                                <div className="mb-4 text-sm font-medium text-muted-foreground">
                                    Podium
                                </div>
                                <div className="grid items-end gap-4 sm:grid-cols-3">
                                    {SLOTS.map(
                                        ({ rank, label, pedestalHeight }) => {
                                            const user = sorted[rank];
                                            if (!user) return null;

                                            const isCurrentUser =
                                                currentUserId === user.id;
                                            const initials = user.name
                                                .split(' ')
                                                .map((part) => part[0])
                                                .join('')
                                                .slice(0, 2)
                                                .toUpperCase();

                                            return (
                                                <div
                                                    key={user.id}
                                                    className={cn(
                                                        'flex flex-col gap-3 rounded-xl border border-border bg-card p-4 text-center shadow-sm',
                                                        rank === 0 &&
                                                            'sm:order-2',
                                                        rank === 1 &&
                                                            'sm:order-1',
                                                        rank === 2 &&
                                                            'sm:order-3',
                                                    )}
                                                >
                                                    <div className="flex flex-col items-center gap-3">
                                                        <Avatar className="size-14 border border-border">
                                                            <AvatarFallback>
                                                                {initials}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <div className="space-y-1">
                                                            <div className="font-semibold">
                                                                {user.name}
                                                            </div>
                                                            <div className="text-sm text-muted-foreground">
                                                                {label}
                                                            </div>
                                                        </div>
                                                        <div className="flex flex-wrap items-center justify-center gap-2">
                                                            {isCurrentUser && (
                                                                <Badge variant="secondary">
                                                                    You
                                                                </Badge>
                                                            )}
                                                            {room.owner ===
                                                                user.id && (
                                                                <Badge variant="outline">
                                                                    Owner
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div
                                                        className={cn(
                                                            'flex items-center justify-center rounded-lg border border-border bg-muted px-4 py-4 text-lg font-semibold',
                                                            pedestalHeight,
                                                        )}
                                                    >
                                                        {user.score} pts
                                                    </div>
                                                </div>
                                            );
                                        },
                                    )}
                                </div>
                                {sorted.length < 3 && (
                                    <div className="mt-4 rounded-lg border border-dashed border-border bg-background/70 px-4 py-3 text-sm text-muted-foreground">
                                        Fewer than three players finished this
                                        match, so the podium shows only the
                                        available places.
                                    </div>
                                )}
                            </div>
                            <div className="rounded-xl border border-border bg-card">
                                <div className="px-5 py-4 sm:px-6">
                                    <div className="text-sm font-medium">
                                        Full standings
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        Ranked by final score.
                                    </div>
                                </div>
                                <Separator />
                                <div className="divide-y divide-border">
                                    {sorted.map((user, index) => {
                                        const isCurrentUser =
                                            currentUserId === user.id;
                                        const initials = user.name
                                            .split(' ')
                                            .map((part) => part[0])
                                            .join('')
                                            .slice(0, 2)
                                            .toUpperCase();

                                        return (
                                            <div
                                                key={user.id}
                                                className={cn(
                                                    'flex items-center gap-3 px-5 py-4 sm:px-6',
                                                    isCurrentUser &&
                                                        'bg-muted/30',
                                                )}
                                            >
                                                <div className="w-8 text-sm font-medium text-muted-foreground">
                                                    #{index + 1}
                                                </div>
                                                <Avatar className="size-10 border border-border">
                                                    <AvatarFallback>
                                                        {initials}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <div className="truncate font-medium">
                                                            {user.name}
                                                        </div>
                                                        {isCurrentUser && (
                                                            <Badge variant="secondary">
                                                                You
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    <div className="text-sm text-muted-foreground">
                                                        {user.correct_guesses ??
                                                            0}{' '}
                                                        correct guesses
                                                    </div>
                                                </div>
                                                <div className="text-right text-sm font-semibold">
                                                    {user.score} pts
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <Button
                                onClick={() => router.visit(`/room/${room.id}`)}
                            >
                                Back to Lobby
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => leaveRoom(room.id)}
                            >
                                Leave Room
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
