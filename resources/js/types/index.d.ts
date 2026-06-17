import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';
import React from 'react';

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    is_guest?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Room {
    id: string;
    name: string;
    users: Array<{
        id: string;
        name: string;
        score: number;
        guesses: number;
        correct_guesses: number;
        guessed: boolean;
    }>;
    chat: Array<{
        user_id: string;
        user: string;
        message: string;
    }>;
    status: {
        time: number;
        term: string;
    };
    canvas: Array<{
        x: number;
        y: number;
        size: number;
        emoji: string;
    }>;
    settings: {
        cap: number;
        password: string;
        public: boolean;
        timeLimit: number;
        [key: string]: unknown;
    };
    owner: string;
    artist: string;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedResponse<T> {
    current_page: number;
    data: T[];
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: PaginationLink[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}

export interface RoomIndexRow {
    id: string;
    name: string;
    players: string;
}

export type setRoomState = React.Dispatch<React.SetStateAction<Room>>;
