import { sendRoomConnectionHeartbeat } from '@/requests/room/room';
import { echo } from '@laravel/echo-react';
import { useEffect, useRef } from 'react';

const tabStorageKey = 'room-connection-tab-id';

type EchoConnection = {
    state: string;
    bind: (eventName: string, callback: (...args: any[]) => void) => void;
    unbind: (eventName: string, callback: (...args: any[]) => void) => void;
};

const getTabId = (): string => {
    const existingTabId = window.sessionStorage.getItem(tabStorageKey);

    if (existingTabId) {
        return existingTabId;
    }

    const nextTabId = window.crypto.randomUUID();
    window.sessionStorage.setItem(tabStorageKey, nextTabId);

    return nextTabId;
};

export function useRoomConnectionLease(
    roomId: string,
    heartbeatIntervalSeconds: number,
): void {
    const heartbeatIntervalRef = useRef<number | null>(null);
    const tabIdRef = useRef<string | null>(null);

    useEffect(() => {
        const echoInstance = echo();
        const connection = (echoInstance.connector as {
            pusher: { connection: EchoConnection };
        }).pusher.connection;

        if (tabIdRef.current === null) {
            tabIdRef.current = getTabId();
        }

        const stopHeartbeats = () => {
            if (heartbeatIntervalRef.current !== null) {
                window.clearInterval(heartbeatIntervalRef.current);
                heartbeatIntervalRef.current = null;
            }
        };

        const sendHeartbeat = async () => {
            if (tabIdRef.current === null) {
                return;
            }

            try {
                await sendRoomConnectionHeartbeat(roomId, tabIdRef.current);
            } catch {
                return;
            }
        };

        const startHeartbeats = () => {
            stopHeartbeats();
            void sendHeartbeat();
            heartbeatIntervalRef.current = window.setInterval(() => {
                void sendHeartbeat();
            }, heartbeatIntervalSeconds * 1000);
        };

        const handleConnected = () => {
            startHeartbeats();
        };

        const handleStateChange = (event: { current: string }) => {
            if (event.current === 'connected') {
                startHeartbeats();

                return;
            }

            stopHeartbeats();
        };

        const handleDisconnected = () => {
            stopHeartbeats();
        };

        connection.bind('connected', handleConnected);
        connection.bind('state_change', handleStateChange);
        connection.bind('unavailable', handleDisconnected);
        connection.bind('disconnected', handleDisconnected);

        if (connection.state === 'connected') {
            startHeartbeats();
        }

        return () => {
            stopHeartbeats();
            connection.unbind('connected', handleConnected);
            connection.unbind('state_change', handleStateChange);
            connection.unbind('unavailable', handleDisconnected);
            connection.unbind('disconnected', handleDisconnected);
        };
    }, [heartbeatIntervalSeconds, roomId]);
}
