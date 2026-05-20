import { CountdownClock } from '@/components/components/countdown-clock';
import { Button } from '@/components/ui/button';
import { useSocket } from '@/connection/echo';
import { sendStroke } from '@/requests/room/room';
import { Room } from '@/types';
import data from '@emoji-mart/data';
import EmojiPicker from '@emoji-mart/react';
import { configureEcho } from '@laravel/echo-react';
import { useEffect, useRef, useState } from 'react';

configureEcho({
    broadcaster: 'reverb',
    wssPort: 443,
});

const CANVAS_LOGICAL_SIZE = 3000;
const MIN_CANVAS_SIZE = 260;
const MAX_CANVAS_SIZE = 900;
const CANVAS_HEIGHT_RATIO = 0.68;

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
    const canvasWrapperRef = useRef<HTMLDivElement>(null);
    const [canvasDisplaySize, setCanvasDisplaySize] = useState<number>(
        MIN_CANVAS_SIZE,
    );
    const [size, setSize] = useState<number>(100);
    const [emoji, setEmoji] = useState<string>('💩');
    const [select, setSelect] = useState<boolean>(false);

    const updateCanvasScale = () => {
        const wrapper = canvasWrapperRef.current;
        if (!wrapper) return;

        const widthLimit = Math.floor(wrapper.getBoundingClientRect().width);
        const heightLimit = Math.floor(window.innerHeight * CANVAS_HEIGHT_RATIO);

        const nextSize = Math.max(
            MIN_CANVAS_SIZE,
            Math.min(widthLimit, heightLimit, MAX_CANVAS_SIZE),
        );

        setCanvasDisplaySize((prev) => (prev === nextSize ? prev : nextSize));
    };

    const { listen: listenStroke } = useSocket(
        `room.${roomId}`,
        'CanvasStroke',
        (e) => {
            if (isArtist) return;
            canvasStroke(e.stroke);
        },
    );

    const { listen: listenClear } = useSocket(`room.${roomId}`, 'ClearCanvas', () => {
        if (canvasRef.current) {
            const canvas = canvasRef.current;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    });

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

    useEffect(() => {
        updateCanvasScale();

        const observer = new ResizeObserver(updateCanvasScale);
        if (canvasWrapperRef.current) {
            observer.observe(canvasWrapperRef.current);
        }

        window.addEventListener('resize', updateCanvasScale);

        return () => {
            observer.disconnect();
            window.removeEventListener('resize', updateCanvasScale);
        };
    }, []);

    const canvasStroke = (stroke: Room['canvas'][number]) => {
        if (!stroke) return;
        const ctx = canvasRef.current?.getContext('2d');
        if (!ctx) return;
        ctx.font = `${isArtist ? size : stroke.size}px Arial`;
        ctx.textAlign = 'center';
        ctx.fillText(stroke.emoji, stroke.x, stroke.y);
        if (isArtist) sendStroke(roomId, stroke);
    };

    return (
        <div
            className={
                className +
                'flex flex-col items-center justify-center gap-5 border border-accent p-6 md:p-10'
            }
        >
            <div
                className={
                    'flex w-full flex-row flex-wrap items-center justify-center gap-4'
                }
            >
                {isArtist ? (
                    <>
                        <span className="text-lg font-semibold tracking-wide">
                            {term}
                        </span>
                        <div>
                            <Button
                                className={'py-10 text-4xl'}
                                onClick={() => setSelect(!select)}
                            >
                                {emoji}
                            </Button>
                        </div>
                        <div
                            className={`absolute z-20 ${select ? 'block' : 'hidden'}`}
                        >
                            <EmojiPicker
                                data={data}
                                type={'native'}
                                onEmojiSelect={(selected: { native: string }) =>
                                    setEmoji(selected.native)
                                }
                                onClickOutside={(event: MouseEvent) => {
                                    if (!select) return;
                                    setSelect(false);
                                    event.stopPropagation();
                                }}
                            />
                        </div>
                        <input
                            type="range"
                            min={50}
                            max={1000}
                            step={1}
                            value={size}
                            onChange={(e) => setSize(Number(e.target.value))}
                            className="rounded-md border border-accent p-2"
                        />
                    </>
                ) : (
                    <span className="font-mono text-xl tracking-widest">
                        {hint || term.replace(/[^ ]/g, '_').split('').join(' ')}
                    </span>
                )}
                <div
                    className={
                        'relative flex flex-row items-center justify-center'
                    }
                >
                    <CountdownClock
                        seconds={timeLeft}
                        defaultTime={roundDuration}
                        roomId={roomId}
                    />
                </div>
            </div>
            <div
                ref={canvasWrapperRef}
                className="flex w-full items-center justify-center"
            >
                <canvas
                    width={CANVAS_LOGICAL_SIZE}
                    height={CANVAS_LOGICAL_SIZE}
                    style={{
                        width: `${canvasDisplaySize}px`,
                        height: `${canvasDisplaySize}px`,
                    }}
                    className="max-w-full rounded-md bg-gray-300"
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
                ></canvas>
            </div>
        </div>
    );
}
