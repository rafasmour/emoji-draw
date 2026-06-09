import { Button } from '@/components/ui/button';
import { leaveRoom } from '@/requests/room/room';
import { Room } from '@/types';
import { router, usePage } from '@inertiajs/react';

interface ResultsRoom {
    id: string;
    name: string;
    owner: string;
    users: Room['users'];
}

interface PodiumSlot {
    rank: number;
    medal: string;
    boxHeight: string;
}

const SLOTS: PodiumSlot[] = [
    { rank: 1, medal: '🥈', boxHeight: 'h-28' },
    { rank: 0, medal: '🥇', boxHeight: 'h-40' },
    { rank: 2, medal: '🥉', boxHeight: 'h-20' },
];

export default function Results() {
    const props = usePage().props;
    const room = props.room as ResultsRoom;
    const currentUser = props.auth.user;

    const sorted = [...room.users].sort((a, b) => b.score - a.score);
    const rest = sorted.slice(3);

    return (
        <div className="flex min-h-screen flex-col items-center justify-center gap-10 p-10">
            <h1 className="text-4xl font-bold">{room.name} — Results</h1>

            <div className="flex items-end gap-6">
                {SLOTS.map(({ rank, medal, boxHeight }) => {
                    const user = sorted[rank];
                    if (!user) return null;
                    return (
                        <div key={user.id} className="flex flex-col items-center gap-2">
                            <span className="text-3xl">{medal}</span>
                            <span
                                className={`font-semibold ${currentUser?.id === user.id ? 'text-yellow-500' : ''}`}
                            >
                                {user.name}
                            </span>
                            <span className="text-sm text-muted-foreground">
                                {user.score} pts
                            </span>
                            <div
                                className={`flex w-24 items-center justify-center rounded-t-lg border border-accent bg-muted font-bold ${boxHeight}`}
                            >
                                #{rank + 1}
                            </div>
                        </div>
                    );
                })}
            </div>

            {rest.length > 0 && (
                <div className="w-full max-w-sm rounded-lg border border-accent">
                    {rest.map((user, i) => (
                        <div
                            key={user.id}
                            className="flex items-center justify-between border-b border-accent px-4 py-3 last:border-b-0"
                        >
                            <span className="flex items-center gap-2">
                                <span className="text-muted-foreground">#{i + 4}</span>
                                <span
                                    className={currentUser?.id === user.id ? 'text-yellow-500' : ''}
                                >
                                    {user.name}
                                </span>
                            </span>
                            <span className="text-sm font-semibold text-muted-foreground">
                                {user.score} pts
                            </span>
                        </div>
                    ))}
                </div>
            )}

            <div className="flex gap-4">
                <Button onClick={() => router.visit(`/room/${room.id}`)}>
                    Back to Lobby
                </Button>
                <Button variant="outline" onClick={() => leaveRoom(room.id)}>
                    Leave Room
                </Button>
            </div>
        </div>
    );
}
