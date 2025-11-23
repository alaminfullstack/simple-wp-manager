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
    port: number;
    username: string;
    path: string;
    private_key: string;
    active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Site {
    id: number;
    server_id: number;
    domain: string;
    container_name: string;
    status: 'pending' | 'running' | 'stopped' | 'failed' | 'deploying';
    database_name: string;
    database_user: string;
    database_password: string;
    admin_username: string;
    admin_email: string;
    admin_password: string;
    ssl: { enabled: boolean } | null;
    firewall_rules: { allow_http: boolean; allow_https: boolean; allow_ssh: boolean } | null;
    last_deployed_at: string | null; // ISO 8601 date string
    created_at: string;
    updated_at: string;
    server: Server; // Nested server object
}

export interface WordPressSite {
    id: number;
    server_id: number;
    domain: string;
    container_name: string;
    status: 'pending' | 'running' | 'stopped' | 'failed' | 'deploying';
    name: string;
    url: string;
    status_message: string;
    masked_server_ip: string;
    wp_version: string;
    ssl_enabled: boolean | null;
    last_checked_at : string | null;
    server_ip: string;
    server_port: string;
    server_username: string;
    container_port: string;
    db_name: string;
    db_user: string;
    db_host: string;
    database_password: string;
    admin_username: string;
    admin_email: string;
    admin_password: string;
    ssl: { enabled: boolean } | null;
    firewall_rules: { allow_http: boolean; allow_https: boolean; allow_ssh: boolean } | null;
    last_deployed_at: string | null; // ISO 8601 date string
    created_at: string;
    updated_at: string;
    server: Server; // Nested server object
}

// For pages that list servers, we might get a count instead of the full relation
export interface ServerWithCount extends Server {
    sites_count: number;
}
