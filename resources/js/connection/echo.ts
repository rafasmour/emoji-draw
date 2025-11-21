import { configureEcho, useEcho } from '@laravel/echo-react';

configureEcho({
    broadcaster: 'reverb',
    wssPort: 443,
})
export const useSocket = (channel: string, eventNames: string| string[], callback: (payload: unknown) => void) => {
    return useEcho(
        channel,
        eventNames,
        callback,
    )
}
