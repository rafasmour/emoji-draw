import { configureEcho, useEcho } from '@laravel/echo-react';
import { EventName, EventPayload } from '@/types/events';

configureEcho({
    broadcaster: 'reverb',
    wssPort: 443,
})

export const useSocket = <E extends EventName>(
    channel: string,
    eventNames: E | E[],
    callback: (payload: EventPayload<E>) => void,
) => {
    return useEcho(
        channel,
        eventNames,
        (payload: EventPayload<E>) => {
            console.log(`[ws] ${channel} / ${Array.isArray(eventNames) ? eventNames.join(',') : eventNames}`, payload);
            callback(payload);
        },
    )
}
