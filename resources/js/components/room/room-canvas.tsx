import { CountdownClock } from '@/components/components/countdown-clock';
import { RoomCanvasArtistTools } from '@/components/room/room-canvas-artist-tools';
import { RoomChatMessageForm } from '@/components/room/room-chat-message-form';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useSocket } from '@/connection/echo';
import { cn } from '@/lib/utils';
import { sendGuess, sendStroke } from '@/requests/room/room';
import { Room } from '@/types';
import { configureEcho } from '@laravel/echo-react';
import { FormEvent, useEffect, useRef, useState } from 'react';

configureEcho({
    broadcaster: 'reverb',
    wssPort: 443,
});

const CANVAS_LOGICAL_SIZE = 3000;

interface RoomCanvasProps {
    defaultStrokes: Room['canvas'];
    isArtist: boolean;
    className?: string;
    term: string;
    hint: string;
    timeLeft: number;
    roundDuration: number;
    roomId: string;
}

export function RoomCanvas({
    defaultStrokes = [],
    isArtist,
    className,
    term,
    hint,
    roomId,
    timeLeft,
    roundDuration,
}: RoomCanvasProps) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const [size, setSize] = useState<number>(100);
    const [emoji, setEmoji] = useState<string>('💩');
    const [guessMessage, setGuessMessage] = useState<string>('');

    const canvasStroke = (stroke: Room['canvas'][number]) => {
        if (!stroke) return;

        const ctx = canvasRef.current?.getContext('2d');
        if (!ctx) return;

        ctx.font = `${isArtist ? size : stroke.size}px Arial`;
        ctx.textAlign = 'center';
        ctx.fillText(stroke.emoji, stroke.x, stroke.y);

        if (isArtist) {
            sendStroke(roomId, stroke);
        }
    };

    const { listen: listenStroke } = useSocket(
        `room.${roomId}`,
        'CanvasStroke',
        (e) => {
            if (isArtist) return;
            canvasStroke(e.stroke);
        },
    );

    const { listen: listenClear } = useSocket(
        `room.${roomId}`,
        'ClearCanvas',
        () => {
            if (canvasRef.current) {
                const canvas = canvasRef.current;
                const ctx = canvas.getContext('2d');
                if (!ctx) return;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
        },
    );

    useEffect(() => {
        listenStroke();
        listenClear();
    }, [listenStroke, listenClear]);

    useEffect(() => {
        if (canvasRef.current) {
            const ctx = canvasRef.current.getContext('2d');
            if (!ctx) return;
            ctx.clearRect(0, 0, CANVAS_LOGICAL_SIZE, CANVAS_LOGICAL_SIZE);

            for (const stroke of defaultStrokes) {
                ctx.font = `${stroke.size}px Arial`;
                ctx.textAlign = 'center';
                ctx.fillText(stroke.emoji, stroke.x, stroke.y);
            }
        }
    }, [defaultStrokes]);

    const submitGuess = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const trimmedGuess = guessMessage.trim();

        if (trimmedGuess.length === 0) {
            return;
        }

        sendGuess(roomId, trimmedGuess);
        setGuessMessage('');
    };

    return (
        <Card
            className={cn(
                'h-screen max-h-screen gap-0 overflow-hidden p-0 sm:h-auto sm:max-h-none xl:h-full xl:max-h-full',
                className,
            )}
        >
            <CardHeader className="shrink-0 items-center gap-4 border-b border-border px-5 py-5 text-center sm:px-6 lg:items-stretch lg:text-left">
                <div className="flex flex-col items-center gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-1">
                        <CardTitle className="text-xl">
                            Round in progress
                        </CardTitle>
                        <CardDescription>
                            {isArtist
                                ? 'You are drawing. Pick an emoji and place it on the board.'
                                : 'Watch the board and submit your guess in chat.'}
                        </CardDescription>
                    </div>
                    <div className="flex items-center justify-center gap-3 self-center rounded-lg border border-border bg-muted/40 px-3 py-2 lg:self-start">
                        <div className="text-center lg:text-right">
                            <div className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                Time left
                            </div>
                            <div className="text-sm font-medium">
                                Current round
                            </div>
                        </div>
                        <CountdownClock
                            seconds={timeLeft}
                            defaultTime={roundDuration}
                            roomId={roomId}
                        />
                    </div>
                </div>
                <div
                    className={`${isArtist ? 'lg:grid-cols-2' : ''}  grid w-full gap-4 text-center xl:text-left`}
                >
                    <div
                        className={
                            `rounded-lg ${!isArtist ? 'text-center' : ''} border border-border bg-muted/30 p-4`
                        }
                    >
                        <div className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                            {isArtist ? 'Prompt' : 'Hint'}
                        </div>
                        <div className="mt-2 font-mono text-lg tracking-[0.25em] break-words sm:text-xl">
                            {isArtist
                                ? term
                                : hint ||
                                  term
                                      .replace(/[^ ]/g, '_')
                                      .split('')
                                      .join(' ')}
                        </div>
                    </div>
                    {isArtist ? (
                        <RoomCanvasArtistTools
                            emoji={emoji}
                            size={size}
                            onEmojiChange={setEmoji}
                            onSizeChange={setSize}
                        />
                    ) : (
                        <div className="rounded-lg border border-dashed border-border bg-background/70 p-4 lg:hidden">
                            <div className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                Your guess
                            </div>
                            <RoomChatMessageForm
                                value={guessMessage}
                                onChange={setGuessMessage}
                                onSubmit={submitGuess}
                                placeholder="Type your guess"
                                className="mt-3"
                                inputClassName="bg-background"
                            />
                        </div>
                    )}
                </div>
            </CardHeader>
            <CardContent className="flex min-h-0 flex-1 items-center justify-center overflow-hidden bg-muted/20 p-3 sm:p-5">
                <canvas
                    width={CANVAS_LOGICAL_SIZE}
                    height={CANVAS_LOGICAL_SIZE}
                    className="aspect-square h-auto max-h-full w-full max-w-3xl touch-manipulation rounded-lg border border-border bg-card shadow-sm xl:h-full xl:w-auto"
                    ref={canvasRef}
                    onClick={(e: React.MouseEvent<HTMLCanvasElement>) => {
                        if (!isArtist) return;
                        const canvas = canvasRef.current;
                        const rect = canvas?.getBoundingClientRect();
                        if (!rect || !canvas) return;
                        const scaleX = canvas.width / rect.width;
                        const scaleY = canvas.height / rect.height;

                        canvasStroke({
                            x: Math.round((e.clientX - rect.left) * scaleX),
                            y: Math.round((e.clientY - rect.top) * scaleY),
                            emoji,
                            size,
                        });
                    }}
                />
            </CardContent>
        </Card>
    );
}
