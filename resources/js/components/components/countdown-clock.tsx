import { useSocket } from '@/connection/echo';
import { configureEcho } from '@laravel/echo-react';
import { useEffect } from 'react';
import { useTimer } from 'react-timer-and-stopwatch';

configureEcho({
    broadcaster: 'reverb',
    wssPort: 443,
});
interface CountdownClockProps {
    seconds: number;
    defaultTime: number;
    roomId: string;
}
export function CountdownClock({
    seconds,
    defaultTime,
    roomId,
}: CountdownClockProps) {
    const time = useTimer({
        create: {
            timerWithDuration: {
                time: {
                    seconds: defaultTime,
                },
            },
        },
    });
    const { listen: listenStartRound } = useSocket(
        `room.${roomId}`,
        'StartRound',
        (e) => {
            time.resetTimer();
        },
    );
    useEffect(() => {
        listenStartRound();
        // for someone that joins late
        time.subtractTime({ seconds: (seconds - defaultTime) * -1 });
    }, [seconds]);
    const secondsLeft = () => {
        if (time.timerText === '00:00:00') return 0;
        const [hours, minutes, seconds] = time.timerText.split(':');
        return (
            parseInt(hours) * 3600 + parseInt(minutes) * 60 + parseInt(seconds)
        );
    };
    return (
        <div
            className={
                'flex h-10 w-10 items-center justify-center overflow-hidden rounded-full border border-accent p-5 shadow-sm shadow-primary'
            }
        >
            <div className={'text-center'}>{secondsLeft()}</div>
        </div>
    );
}
