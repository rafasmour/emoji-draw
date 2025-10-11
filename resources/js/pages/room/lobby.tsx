import { Button } from '@/components/ui/button';
import { useEchoLobby } from '@/events/room/useLobbyEvents';
import {
    changeOwner,
    destroyRoom,
    kickPlayer,
    leaveRoom,
    sendMessage,
    startGame,
} from '@/requests/room/room';
import { Room } from '@/types';
import { usePage } from '@inertiajs/react';
import { CircleX, CrownIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
export default function Lobby() {
    const props = usePage().props;
    const defaultRoom: Room = props.room as Room;
    const currentUser = props.auth.user;
    const [room, setRoom] = useState<Room>(defaultRoom);
    const [message, setMessage] = useState('');
    const chatContainerRef = useRef<HTMLDivElement>(null);
    const users = room.users;
    const { stopListening, listen } = useEchoLobby(room.id, setRoom, currentUser.id);
    useEffect(() => {
        listen();
        return () => {
            stopListening();
        };
    });

    useEffect(() => {
        const owner = room.users.find((user) => user.id === room.owner);
        console.log(owner?.name);
    }, [room]);
    useEffect(() => {
        if (chatContainerRef.current) {
            chatContainerRef.current.scrollTo({
                top: chatContainerRef.current.scrollHeight,
                behavior: 'smooth',
            })
        }
    }, [room.chat, chatContainerRef]);
    const isOwner = room.owner === currentUser.id;
    return (
        <div className={'flex flex-col gap-4'}>
            <div className={'flex flex-col gap-4 border border-accent'} >
                {users &&
                    users.length > 0 &&
                    users?.map((user, index) => {
                        return (
                            <div
                                key={`user-${user.id}-${index}`}
                                className={'flex flex-row gap-4 p-4'}
                            >
                                <div>
                                    {user.name}{' '}
                                    {room.owner === user.id && '(Owner) 👑'}
                                </div>

                                {isOwner && user.id !== props.auth.user.id && (
                                    <div className="flex flex-row gap-2">
                                        <Button
                                            onClick={() =>
                                                changeOwner(room.id, user.id)
                                            }
                                        >
                                            <CrownIcon />
                                        </Button>
                                        <Button
                                            onClick={() =>
                                                kickPlayer(room.id, user.id)
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
            <div className={'flex flex-wrap gap-4'}>
                <Button onClick={() => leaveRoom(room.id)}>Leave</Button>
                <Button onClick={() => destroyRoom(room.id)}>
                    Destroy Room
                </Button>
                <Button onClick={() => startGame(room.id)}>Start Game</Button>
            </div>
            <div>
                <div className={'max-h-90 overflow-y-auto scroll-smooth'} ref={chatContainerRef}>
                    {room.chat.length > 0 &&
                        room.chat.map((chat, index) => (
                            <div key={`user-${chat.user_id}-${index}`}>
                                <i>{chat.user}</i>:{' '}
                                <span>{chat.message}</span>{' '}
                            </div>
                        ))}
                </div>
                <div>
                    <input
                        value={message}
                        onChange={(e) => setMessage(e.target.value)}
                        type="text"
                        placeholder="Message"
                    />
                    <button
                        onClick={() => {
                            sendMessage(room.id, message);
                            setMessage('');
                        }}
                    >
                        Send
                    </button>
                </div>
            </div>
        </div>
    );
}
