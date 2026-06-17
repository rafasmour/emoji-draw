import { RoomChatMessageForm } from '@/components/room/room-chat-message-form';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useSocket } from '@/connection/echo';
import { cn } from '@/lib/utils';
import { sendGuess, sendMessage } from '@/requests/room/room';
import { Room } from '@/types';
import { configureEcho } from '@laravel/echo-react';
import { FormEvent, useEffect, useRef, useState } from 'react';

interface RoomChatProps {
    roomId: string;
    defaultChat: Room['chat'];
    className?: string;
    guess?: boolean;
    showForm?: boolean;
}

configureEcho({
    broadcaster: 'reverb',
    wssPort: 443,
});

export function RoomChat({
    roomId,
    defaultChat,
    className,
    guess = false,
    showForm = true,
}: RoomChatProps) {
    const [chat, setChat] = useState<Room['chat']>(defaultChat ?? []);
    const [message, setMessage] = useState<string>('');
    const messageListRef = useRef<HTMLDivElement>(null);
    const { listen: listenNewMessage } = useSocket(
        `room.${roomId}`,
        'ChatMessage',
        (e) => setChat((prev) => [...prev, e.message]),
    );
    const { listen: listenClear } = useSocket(
        `room.${roomId}`,
        'ClearChat',
        () => setChat([]),
    );

    useEffect(() => {
        listenClear();
        listenNewMessage();
    }, [listenNewMessage, listenClear]);

    useEffect(() => {
        const node = messageListRef.current;
        if (!node) return;
        node.scrollTop = node.scrollHeight;
    }, [chat]);

    const submitMessage = (event?: FormEvent<HTMLFormElement>) => {
        event?.preventDefault();

        const trimmedMessage = message.trim();

        if (trimmedMessage.length === 0) {
            return;
        }

        if (guess) {
            sendGuess(roomId, trimmedMessage);
        } else {
            sendMessage(roomId, trimmedMessage);
        }

        setMessage('');
    };

    return (
        <Card className={cn('gap-0 overflow-hidden p-0', className)}>
            <CardHeader className="border-b border-border px-5 py-5 sm:px-6">
                <CardTitle className="text-lg">
                    {guess ? 'Guesses' : 'Chat'}
                </CardTitle>
                <CardDescription>
                    {guess
                        ? 'Submit guesses here while the round is running.'
                        : 'Send messages to everyone in the room.'}
                </CardDescription>
            </CardHeader>
            <CardContent
                ref={messageListRef}
                className="flex h-full max-h-[50vh] flex-col gap-3 overflow-y-scroll bg-muted/20 p-4"
            >
                {chat.length > 0 ? (
                    chat.map((chatEntry, index) => (
                        <div
                            key={`user-${chatEntry.user_id}-${index}`}
                            className="rounded-lg border border-border bg-card px-3 py-2 text-sm break-words"
                        >
                            <span className="font-medium">
                                {chatEntry.user}
                            </span>
                            <span className="text-muted-foreground">: </span>
                            <span>{chatEntry.message}</span>
                        </div>
                    ))
                ) : (
                    <div className="flex h-full items-center justify-center text-center text-sm text-muted-foreground">
                        {guess
                            ? 'No guesses yet. The next message can change the round.'
                            : 'No messages yet.'}
                    </div>
                )}
            </CardContent>
                <CardFooter className="hidden w-full lg:flex lg:items-center lg:justify-center gap-2 border-t border-border p-0">
                    <RoomChatMessageForm
                        value={message}
                        onChange={setMessage}
                        onSubmit={submitMessage}
                        placeholder={
                            guess ? 'Type your guess' : 'Write a message'
                        }
                        className="max-w-[95%] py-2 flex items-center justify-center"
                    />
                </CardFooter>
        </Card>
    );
}
