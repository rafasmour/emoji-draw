import { Button } from '@/components/ui/button';
import { changeOwner, kickPlayer } from '@/requests/room/room';
import { Room } from '@/types';
import { configureEcho, useEcho } from '@laravel/echo-react';
import { CircleX, CrownIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

export interface RoomUsersProps {
    roomId: string;
    defaultUsers: Room['users'];
    className?: string;
    owner: string;
    artist?: string;
    currentUserId: string;
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
    artist
}: RoomUsersProps) {
    const [users, setUsers] = useState<Room['users']>(defaultUsers);
    const isOwner = owner === currentUserId;
    const { listen: listenJoin } = useEcho(`room.${roomId}`, 'Join', (e) => {
        setUsers((prev) => [...prev, e.user]);
    });
    const { listen: listenLeave } = useEcho(`room.${roomId}`, 'Leave', (e) => {
        setUsers((prev) => prev.filter((user) => user.id !== e.user_id));
    });
    const { listen: listenKick } = useEcho(
        `room.${roomId}`,
        'PlayerKicked',
        (e) => {
            if(e.user_id === currentUserId){
                alert('You have been kicked from the room... ');
                window.location.reload();
            }
            setUsers((prev) => prev.filter((user) => user.id !== e.user_id));
        },
    );
    useEffect(() => {
        listenJoin();
        listenLeave();
        listenKick();
    }, [listenJoin, listenLeave, listenKick]);
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
                            className={'flex w-full flex-row gap-4 p-4'}
                        >
                            <div>
                                {user.name}{' '}
                                {owner === user.id && '(Owner) 👑'}
                                {artist === user.id && '(Artist) 🖌️'}
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
