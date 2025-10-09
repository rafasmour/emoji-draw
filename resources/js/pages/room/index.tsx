import { Link, router, usePage } from '@inertiajs/react';
import { type Room } from '@/types';
import { Button } from '@/components/ui/button';
import { joinRoom } from '@/requests/room/room';
export default function Room() {
    const props = usePage().props;
    const rooms: Array<Partial<Room>> = props.rooms as Array<Partial<Room>>;

    return (
        <div className={"flex flex-wrap gap-4"}>
            {rooms.length > 0 && rooms?.map((room) => {
               return (<div key={`room-${room.id}`} className={"p-4 border-accent flex flex-col gap-4"}>
                   <h1>{room.name}</h1>
                   <Button onClick={()=> room?.id && joinRoom(room.id)}>
                       Join
                   </Button>
               </div>)
            })}
        </div>
    )
}
