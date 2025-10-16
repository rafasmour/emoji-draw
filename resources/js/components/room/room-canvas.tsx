import { Room } from '@/types';
import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import EmojiPicker from '@emoji-mart/react';
import data from '@emoji-mart/data';
import { Button } from '@/components/ui/button';
import { sendStroke } from '@/requests/room/room';
import { configureEcho, useEcho } from '@laravel/echo-react';
configureEcho({
    broadcaster: 'reverb',
    wssPort: 443,
});
interface RoomCanvasProps {
    defaultStrokes: Room['canvas'];
    isArtist: boolean;
    className?: string;
    term: string;
    roomId: string;
}
export function RoomCanvas({
    defaultStrokes = [],
    isArtist,
    className,
    term,
    roomId
}: RoomCanvasProps) {
    const [strokes, setStrokes] =
        useState<Room['canvas']>(defaultStrokes);
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const [size, setSize] = useState<number>(100);
    const [emoji, setEmoji] = useState<string>('💩');
    const [select, setSelect] = useState<boolean>(false);
    const {listen: listenStroke} = useEcho(
        `room.${roomId}`,
        'CanvasStroke',
        (e) => {
            if(isArtist) return;
            console.log(e.stroke);
            setStrokes(prev => [...prev, e.stroke]);
            canvasStroke(e.stroke);
        }
    )
    const {listen: listenClear} = useEcho(`room.${roomId}`, 'ClearCanvas', () => {
        setStrokes([]);
        if(canvasRef.current) {
            const canvas = canvasRef.current;
            const ctx = canvas.getContext('2d');
            if(!ctx) return;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    })
    useEffect(() => {
        listenStroke();
        listenClear();
    });
    useEffect(() => {
        setStrokes(defaultStrokes);
        if (canvasRef.current) {
            console.log(strokes);
            const ctx = canvasRef.current.getContext('2d');
            if(!ctx) return;
            for (const stroke of strokes) {
                ctx.font = `${stroke.size}px Arial`;
                ctx.textAlign = 'center';
                ctx.fillText(stroke.emoji, stroke.x, stroke.y);
            }
        }
    }, []);
    const canvasStroke = (stroke: Room['canvas'][number]) => {
        if(!stroke) return;
        const ctx = canvasRef.current?.getContext('2d');
        if (!ctx) return;
        ctx.font = `${isArtist ? size : stroke.size}px Arial`;
        ctx.textAlign = 'center';
        ctx.fillText(stroke.emoji, stroke.x, stroke.y);
        if(isArtist) sendStroke(roomId, stroke);
    };

    return (
        <div
            className={
                className +
                'flex items-center justify-center gap-5 border border-accent p-10'
            }
        >
            <div className={'flex flex-row gap-4'}>
                {isArtist ? (
                    <>
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
                                    onEmojiSelect={(e) => setEmoji(e.native)}
                                    onClickOutside={(e) =>
                                        select &&
                                        setSelect(false) &&
                                        e.stopPropagation()
                                    }
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
                ) : term}
            </div>
            <canvas
                width={3000}
                height={3000}
                className="h-[90%] w-[90%] bg-gray-300"
                ref={canvasRef}
                onClick={(e: React.MouseEvent<HTMLCanvasElement>) => {
                    if(!isArtist) return;
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
    );
}
