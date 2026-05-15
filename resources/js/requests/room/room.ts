import { Room } from '@/types';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { toast } from 'react-toastify';

const api = axios.create({
    withCredentials: true,
    withXSRFToken: true,
    headers: { Accept: 'application/json' },
});

const navigate = (url: string) => router.visit(url);

const handleError = (error: unknown) => {
    if (axios.isAxiosError(error)) {
        toast.error(error.response?.data?.message ?? 'Something went wrong.');
    } else {
        toast.error('Something went wrong.');
    }
};

export const leaveRoom = async (roomId: string) => {
    try {
        const res = await api.post(`/room/${roomId}/leave`);
        navigate(res.data.redirect);
    } catch (error) {
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
