import { Room } from '@/types';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { toast } from 'react-toastify';

const api = axios.create({
    withCredentials: true,
    withXSRFToken: true,
    headers: { Accept: 'application/json' },
});

const leavingRoomIds = new Set<string>();

const getCsrfToken = (): string =>
    decodeURIComponent(
        document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );

const navigate = (url: string) => router.visit(url);

const handleError = (error: unknown) => {
    
   toast.error(error.response?.data?.message ?? 'Something went wrong.');
};

export const leaveRoom = async (
    roomId: string,
    options: { shouldRedirect?: boolean; useBeacon?: boolean } = {},
) => {
    const { shouldRedirect = true, useBeacon = false } = options;

    if (leavingRoomIds.has(roomId)) {
        return;
    }

    leavingRoomIds.add(roomId);

    try {
        if (useBeacon) {
            const url = `/room/${roomId}/leave`;
            const token = getCsrfToken();
            const data = new FormData();
            data.append('_token', token);
            navigator.sendBeacon(url, data);
            return;
        }
        const res = await api.post(`/room/${roomId}/leave`);
        if (shouldRedirect) {
            navigate(res.data.redirect);
        }
    } catch (error) {
        leavingRoomIds.delete(roomId);
        handleError(error);
    }
};

export const destroyRoom = async (roomId: string) => {
    try {
        const res = await api.delete(`/room/${roomId}`);
        navigate(res.data.redirect);
    } catch (error) {
        handleError(error);
    }
};

export const startGame = async (roomId: string) => {
    try {
        const res = await api.post(`/room/${roomId}/start`);
        navigate(res.data.redirect);
    } catch (error) {
        handleError(error);
    }
};

export const joinRoom = async (roomId: string) => {
    try {
        const res = await api.post('/room/join', { room_id: roomId });
        navigate(res.data.redirect);
    } catch (error) {
        handleError(error);
    }
};

export const changeOwner = async (roomId: string, userId: string) => {
    try {
        await api.patch(`/room/${roomId}/change-owner`, { user_id: userId });
    } catch (error) {
        handleError(error);
    }
};

export const kickPlayer = async (roomId: string, userId: string) => {
    try {
        await api.post(`/room/${roomId}/kick`, { user_id: userId });
    } catch (error) {
        handleError(error);
    }
};

export const sendMessage = async (roomId: string, message: string) => {
    try {
        await api.post(`/room/${roomId}/messages`, { message });
    } catch (error) {
        handleError(error);
    }
};

export const sendGuess = async (roomId: string, guess: string) => {
    try {
        await api.post(`/room/${roomId}/guess`, { guess });
    } catch (error) {
        handleError(error);
    }
};

export const sendStroke = async (roomId: string, stroke: Room['canvas'][number]) => {
    try {
        await api.post(`/room/${roomId}/canvas`, { ...stroke });
    } catch (error) {
        handleError(error);
    }
};

export const sendRoomConnectionHeartbeat = async (
    roomId: string,
    tabId: string,
) => {
    return api.post(`/room/${roomId}/connection/heartbeat`, {
        tab_id: tabId,
    });
};
