import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';
import { route as routeFn } from 'ziggy-js';

declare global {
    var route: typeof routeFn;
}

export interface Auth {
    user: User;
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
    flash: {
        message?: string;
        success?: string;
        error?: string;
    };
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
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Server {
    id: number;
    name: string;
    ip_address: string;
    ssh_port: number;
    ssh_user: string;
    port: number;
    username: string;
    path: string;
    private_key: string;
    status: 'active' | 'inactive' | 'error';
    active: boolean;
    created_at: string;
    updated_at: string;
}

// Server with sites count for list views
export interface ServerListItem extends Server {
    word_press_sites_count?: number;
}


export interface Wordpress {
    id: number;
    is_remote: boolean;
    site_name: string;
    domain: string;
    port: number;
    container_name: string;
    db_name: string;
    db_user: string;
    db_password: string;
    db_root_password: string;
    status: 'creating' | 'running' | 'stopped' | 'error' | 'deploying' | 'deleting' | 'failed';
    error_message?: string | null;
    admin_email: string;
    admin_user: string;
    admin_password: string;
    created_at?: string;
    updated_at?: string;
    server_id?: number;
    server?: Server | null;
}

// For pages that list servers, we might get a count instead of the full relation
export interface ServerWithCount extends Server {
    sites_count: number;
}
