import { Button } from '@/components/ui/button';
import { useIsMobile } from '@/hooks/use-mobile';
import data from '@emoji-mart/data';
import EmojiPicker from '@emoji-mart/react';
import { useRef, useState } from 'react';

interface RoomCanvasArtistToolsProps {
    emoji: string;
    size: number;
    onEmojiChange: (emoji: string) => void;
    onSizeChange: (size: number) => void;
}

export function RoomCanvasArtistTools({
    emoji,
    size,
    onEmojiChange,
    onSizeChange,
}: RoomCanvasArtistToolsProps) {
    const [isEmojiPickerOpen, setIsEmojiPickerOpen] = useState<boolean>(false);
    const isMobile = useIsMobile();
    const mobileEmojiInputRef = useRef<HTMLInputElement>(null);

    const handleEmojiSelect = (selected: { native: string }) => {
        onEmojiChange(selected.native);
        setIsEmojiPickerOpen(false);
    };


    return (
        <div className="grid w-full items-center gap-4 sm:grid-cols-[auto_minmax(0,1fr)]">
            <div className="relative justify-center items-center sm:justify-items-start">
                <div className="text-sm text-center w-full tracking-[0.2em] text-muted-foreground uppercase">
                    Emoji
                </div>
                <div className="mt-2 flex w-full max-w-[22rem] flex-col items-center gap-3 sm:items-start">
                    <Button
                        type="button"
                        variant="outline"
                        className="size-16 touch-manipulation text-3xl"
                        aria-label={`Choose drawing emoji. Current emoji: ${emoji}`}
                        aria-expanded={!isMobile && isEmojiPickerOpen}
                        onClick={() => {
                            if (isMobile) {
                                mobileEmojiInputRef.current?.focus();
                                mobileEmojiInputRef.current?.click();

                                return;
                            }

                            setIsEmojiPickerOpen((isOpen) => !isOpen);
                        }}
                    >
                        {emoji}
                    </Button>
                    <input
                        ref={mobileEmojiInputRef}
                        type="text"
                        inputMode="text"
                        autoCapitalize="off"
                        autoCorrect="off"
                        spellCheck={false}
                        enterKeyHint="done"
                        maxLength={8}
                        className="pointer-events-none absolute opacity-0"
                        aria-hidden="true"
                        tabIndex={-1}
                        onChange={(event) => {
                            const nextEmoji = event.target.value.trim();

                            if (nextEmoji.length > 0) {
                                onEmojiChange(nextEmoji);
                                event.target.value = '';
                                event.target.blur();
                            }
                        }}
                    />
                    {!isMobile && isEmojiPickerOpen ? (
                        <div className="absolute top-full left-1/2 z-[80] mt-3 w-[min(22rem,calc(100vw-2rem))] -translate-x-1/2 overflow-hidden rounded-2xl border border-border bg-background p-2 shadow-xl sm:left-0 sm:translate-x-0">
                            <EmojiPicker
                                data={data}
                                type="native"
                                width="100%"
                                onEmojiSelect={handleEmojiSelect}
                            />
                        </div>
                    ) : null}
                    {!isMobile ? (
                        <div className="sr-only" aria-live="polite">
                            Choose an emoji
                        </div>
                    ) : null}
                    {isMobile ? (
                        <p className="text-center text-sm text-muted-foreground sm:text-left">
                            Tap the emoji button to use your device keyboard.
                        </p>
                    ) : null}
                </div>
            </div>
            <div className={"flex flex-col items-start h-full"}>
                <div className="flex items-center w-full justify-between gap-3 text-left">
                    <div className="text-sm tracking-[0.2em] text-muted-foreground uppercase">
                        Size
                    </div>
                    <div className="text-sm font-medium">{size}px</div>
                </div>
                <input
                    aria-label="Emoji size"
                    type="range"
                    min={50}
                    max={1000}
                    step={1}
                    value={size}
                    onChange={(event) =>
                        onSizeChange(Number(event.target.value))
                    }
                    className="mt-4 h-8 w-full touch-manipulation accent-primary"
                />
            </div>
        </div>
    );
}
