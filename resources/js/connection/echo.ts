import { useEcho } from '@laravel/echo-react';
import { EventName, EventPayload } from '@/types/events';

export const useSocket = <E extends EventName>(
    channel: string,
    eventNames: E | E[],
    callback: (payload: EventPayload<E>) => void,
) => {
    return useEcho(
        channel,
        eventNames,
        (payload: EventPayload<E>) => {
            callback(payload);
        },
    )
}
