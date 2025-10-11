import { router } from '@inertiajs/react';

export const leaveRoom = async (roomId: string) => {
    router.post(`/room/${roomId}/leave`);
};
export const destroyRoom = async (roomId: string) => {
    router.delete(`/room/${roomId}`);
};

export const startGame = async (roomId: string) => {
    router.post(`/room/${roomId}/start`);
};

export const joinRoom =  async (roomId: string) => {
    router.post('/room/join', {'room_id': roomId});
}

export const changeOwner = async (roomId: string, userId: string) => {
    router.patch(`/room/${roomId}/change-owner`, {user_id: userId})
}

export const kickPlayer = async (roomId: string, userId: string) => {
    router.post(`/room/${roomId}/kick`, {user_id: userId})
}

export const sendMessage = async (roomId: string, message: string) => {
    router.post(`/room/${roomId}/messages`, {message: message});
}
