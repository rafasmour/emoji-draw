import { useEffect, useState } from 'react';
import { useTimer } from 'react-timer-and-stopwatch';
import { configureEcho, useEcho } from '@laravel/echo-react';
import { useSocket } from '@/connection/echo';
configureEcho({
    broadcaster: 'reverb',
    wssPort: 443,
});
interface CountdownClockProps {
    seconds: number;
    defaultTime: number;
}
export function CountdownClock({seconds, defaultTime}: CountdownClockProps) {
    const time = useTimer({
        create: {
            timerWithDuration: {
                time: {
                    seconds: defaultTime
                }
            }
        }
    })
    const { listen: listenStartRound} = useSocket('round', 'StartRound', () => {
        time.resumeTimer()
    });
    useEffect(() => {
        listenStartRound();
        // for someone that joins late
        time.subtractTime({seconds: (seconds - defaultTime) * -1});
    }, []);
    const secondsLeft = () => {
        if (time.timerText === '00:00:00') return 0;
        const [hours, minutes, seconds] = time.timerText.split(':');
        return (
            parseInt(hours) * 3600 + parseInt(minutes) * 60 + parseInt(seconds)
        );
    }
    return (
        <div className={"border border-accent flex items-center justify-center rounded-full p-5 w-10 h-10 overflow-hidden shadow-sm shadow-primary"}>
            <div className={"text-center"}>{secondsLeft()}</div>
        </div>
    )
}
