import { useEffect } from 'react';

export const ROUND_CHANGE_DURATION_MS = 3500;

interface RoundChangeOverlayProps {
    round: number;
    totalRounds: number;
    previousTerm: string;
    artistName: string;
    isArtist: boolean;
    onDismiss: () => void;
}

export function RoundChangeOverlay({
    round,
    totalRounds,
    previousTerm,
    artistName,
    isArtist,
    onDismiss,
}: RoundChangeOverlayProps) {
    useEffect(() => {
        const timer = setTimeout(onDismiss, ROUND_CHANGE_DURATION_MS);
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') onDismiss();
        };
        window.addEventListener('keydown', onKeyDown);

        return () => {
            clearTimeout(timer);
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [onDismiss]);

    return (
        <div
            className="fixed inset-0 z-40 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm duration-200 animate-in fade-in"
            role="status"
            aria-live="polite"
            onClick={onDismiss}
        >
            <div className="w-full max-w-md overflow-hidden rounded-xl border border-border bg-card text-center shadow-xl duration-300 animate-in slide-in-from-bottom-4 zoom-in-95">
                <div className="space-y-2 px-6 pt-7 pb-6">
                    <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                        Round {round - 1} is over. The word was
                    </p>
                    <p className="text-4xl font-black tracking-tight break-words text-primary sm:text-5xl">
                        {previousTerm}
                    </p>
                </div>
                <div className="border-t border-border bg-muted/40 px-6 py-4">
                    <p className="text-sm font-medium">
                        Round {round} of {totalRounds}
                        <span className="text-muted-foreground"> · </span>
                        {isArtist
                            ? 'You are drawing'
                            : `${artistName} is drawing`}
                    </p>
                </div>
                <div
                    aria-hidden="true"
                    className="h-1 origin-left bg-accent motion-reduce:hidden"
                    style={{
                        animation: `countdown-shrink ${ROUND_CHANGE_DURATION_MS}ms linear both`,
                    }}
                />
            </div>
        </div>
    );
}
