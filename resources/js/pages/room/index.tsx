import { Link, router, usePage } from '@inertiajs/react';
import { type Room } from '@/types';
import { Button } from '@/components/ui/button';
export default function Room() {
    const props = usePage().props;
    const rooms: Array<Partial<Room>> = props.rooms as Array<Partial<Room>>;
    const joinRoom =  async (roomId: string) => {
        const response = await router.post('/room/join', {'room_id': roomId})
        console.log(await response.data);

    }
    return (
        <div className={"flex flex-wrap gap-4"}>
            {rooms.length > 0 && rooms?.map((room) => {
               return (<div key={`room-${room.id}`} className={"p-4 border-accent flex flex-col gap-4"}>
                   <h1>{room.name}</h1>
                   <Button onClick={()=> joinRoom(room.id)}>
                       Join
                   </Button>
               </div>)
            })}
        </div>
    )
}
