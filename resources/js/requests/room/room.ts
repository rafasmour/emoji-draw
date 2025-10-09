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
    const response = await router.post('/room/join', {'room_id': roomId})
    console.log(await response.data);

}
