import { sendMessage } from '@/requests/room/room';
import { Room } from '@/types';
import { useEffect, useRef, useState } from 'react';
import { configureEcho, useEcho } from '@laravel/echo-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

interface RoomChatProps {
    roomId: string;
    defaultChat: Room['chat'];
    className?: string
}

configureEcho({
    broadcaster: 'reverb',
    wssPort: 443,
});
export function RoomChat({ roomId, defaultChat, className }: RoomChatProps) {
    const [chat, setChat] = useState<Room['chat']>(defaultChat ?? []);
    const [message, setMessage] = useState<string>('');
    const { listen: listenNewMessage } = useEcho(
        `room.${roomId}`,
        'ChatMessage',
        (e) => setChat((prev) => [...prev, e.message]),
        [defaultChat],
    );
    const { listen: listenClear } = useEcho(`room.${roomId}`, 'ClearChat', () =>
        setChat([]),
    );
    useEffect(() => {
        listenClear();
        listenNewMessage();
    }, [listenNewMessage, listenClear]);
    return (
        <div
            className={className + 'grid grid-cols-10 grid-rows-10'}
        >
            <div
                className={
                    'col-span-10 row-span-9  overflow-y-auto text-wrap break-words h-90 max-h-90 flex flex-col justify-end'
                }
            >
                {chat.length > 0 &&
                    chat.map((chat, index) => (
                        <div key={`user-${chat.user_id}-${index}`} >
                            <i>{chat.user}</i>: <span>{chat.message}</span>{' '}
                        </div>
                    ))}
            </div>
            <div className={'col-span-10 row-span-1 flex flex-row'}>
                <Input
                    value={message}
                    onChange={(e) => setMessage(e.target.value)}
                    type="text"
                    placeholder="Message"
                />
                <Button
                    onClick={() => {
                        sendMessage(roomId, message);
                        setMessage('');
                    }}
                >
                    Send
                </Button>
            </div>
        </div>
    );
}
