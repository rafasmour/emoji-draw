import { CSSProperties } from 'react';

const BURST_EMOJIS = ['🎉', '✨', '⭐', '🥳', '💥', '🎯'];
const PARTICLE_COUNT = 14;

export const GUESS_BURST_DURATION_MS = 1600;

interface GuessBurstProps {
    points: number;
}

export function GuessBurst({ points }: GuessBurstProps) {
    return (
        <div
            className="pointer-events-none fixed inset-0 z-50 overflow-hidden"
            role="status"
            aria-live="polite"
        >
            {Array.from({ length: PARTICLE_COUNT }, (_, index) => {
                const angle = (index / PARTICLE_COUNT) * Math.PI * 2;
                const distance = index % 2 === 0 ? 34 : 22;
                const style = {
                    '--burst-x': `${Math.cos(angle) * distance}vmin`,
                    '--burst-y': `${Math.sin(angle) * distance}vmin`,
                    '--burst-rotate': `${index % 2 === 0 ? 200 : -160}deg`,
                    animation: `emoji-burst 1100ms cubic-bezier(0.16, 1, 0.3, 1) ${index * 12}ms both`,
                } as CSSProperties;

                return (
                    <span
                        key={index}
                        aria-hidden="true"
                        className="emoji-burst-particle absolute top-1/2 left-1/2 text-5xl sm:text-6xl"
                        style={style}
                    >
                        {BURST_EMOJIS[index % BURST_EMOJIS.length]}
                    </span>
                );
            })}
            <div className="absolute inset-0 flex flex-col items-center justify-center">
                <div
                    className="score-pop flex flex-col items-center text-center"
                    style={{
                        animation: `score-pop ${GUESS_BURST_DURATION_MS}ms ease-out both`,
                    }}
                >
                    <span className="text-7xl font-black tracking-tight text-primary drop-shadow-sm sm:text-8xl">
                        +{points}
                    </span>
                    <span className="mt-1 text-sm font-semibold tracking-[0.2em] text-foreground uppercase">
                        You got it
                    </span>
                </div>
            </div>
        </div>
    );
}
