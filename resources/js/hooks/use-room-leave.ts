import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { leaveRoom } from '@/requests/room/room';

const isRoomRoute = (pathname: string, roomId: string): boolean =>
    pathname.startsWith(`/room/${roomId}`);

export function useRoomLeave(roomId: string): void {
    const leftRef = useRef(false);

    useEffect(() => {
        const handlePageHide = () => {
            if (leftRef.current) return;
            leftRef.current = true;
            leaveRoom(roomId, { useBeacon: true });
        };
        const handleVisibilityChange = () => {
            if (document.visibilityState === 'hidden') {
                if (leftRef.current) return;
                leftRef.current = true;
                leaveRoom(roomId, { useBeacon: true });
            }
        };

        const removeInertiaListener = router.on('before', (event) => {
            const nextPath = event.detail.visit.url.pathname;
            if (!isRoomRoute(nextPath, roomId)) {
                leaveRoom(roomId, { shouldRedirect: false });
            }
        });

        window.addEventListener('pagehide', handlePageHide);
        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
            window.removeEventListener('pagehide', handlePageHide);
            document.removeEventListener('visibilitychange', handleVisibilityChange);
            removeInertiaListener();
        };
    }, [roomId]);
}
