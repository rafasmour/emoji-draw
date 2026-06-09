import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

const getCsrfToken = (): string =>
    decodeURIComponent(
        document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );

const isRoomRoute = (pathname: string, roomId: string): boolean =>
    pathname.startsWith(`/room/${roomId}`);

export function useRoomLeave(roomId: string): void {
    const leftRef = useRef(false);

    useEffect(() => {
        const sendLeave = (useSendBeacon: boolean): void => {
            if (leftRef.current) return;
            leftRef.current = true;

            const url = `/room/${roomId}/leave`;
            const token = getCsrfToken();

            if (useSendBeacon) {
                const data = new FormData();
                data.append('_token', token);
                navigator.sendBeacon(url, data);
                return;
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                keepalive: true,
            });
        };

        const handleBeforeUnload = () => sendLeave(true);
        const handlePageHide = () => sendLeave(true);

        const removeInertiaListener = router.on('before', (event) => {
            const nextPath = event.detail.visit.url.pathname;
            if (!isRoomRoute(nextPath, roomId)) {
                sendLeave(false);
            }
        });

        window.addEventListener('beforeunload', handleBeforeUnload);
        window.addEventListener('pagehide', handlePageHide);

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
            window.removeEventListener('pagehide', handlePageHide);
            removeInertiaListener();
        };
    }, [roomId]);
}
