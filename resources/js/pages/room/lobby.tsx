import { router, usePage } from '@inertiajs/react';
import Pusher from 'pusher-js';
import { Room } from '@/types';
import { Button } from '@/components/ui/button';


export default function Lobby() {
    const props = usePage().props;
    const room: Partial<Room> = props.room as Partial<Room>;
    const users = room.users;
    const leaveRoom = () => {
        router.post(`/room/${room.id}/leave`)
    }
    const destroyRoom = () => {
        router.delete(`/room/${room.id}`);
    }

    const startGame = () => {
        router.post(`/room/${room.id}/start`);
    }
    console.log(props);
    return (
        <div className={"flex flex-col gap-4"}>
            <div className={' border-accent border flex flex-col gap-4'}>
                {users && users.length > 0 && users?.map((user) => {
                    return (
                        <div key={`user-${user.id}`} className={'p-4'}>
                            {user.name} {room.owner === user.id && '(Owner) 👑'}
                        </div>
                    )
                })}
            </div>
            <div className={"flex flex-wrap gap-4"}>
                <Button onClick={() => leaveRoom()}>Leave</Button>
                <Button onClick={() => destroyRoom()}>Destroy Room</Button>
                <Button onClick={() => startGame()}>Start Game</Button>
            </div>
        </div>
    )
}
