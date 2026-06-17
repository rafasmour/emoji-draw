import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { router } from '@inertiajs/react';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

function generateRandomString(length: number): string {
    const characters =
        'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let randomString = '';
    for (let i = 0; i < length; i++) {
        const randomIndex = Math.floor(Math.random() * characters.length);
        randomString += characters.charAt(randomIndex);
    }
    return randomString;
}
export const createRoom = async () => {
    await router.post('/room/create', { name: generateRandomString(10) }, {});
};
