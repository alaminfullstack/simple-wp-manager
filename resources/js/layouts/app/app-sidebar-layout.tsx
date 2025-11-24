import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { type BreadcrumbItem } from '@/types';
import { type PropsWithChildren } from 'react';
import { usePage } from '@inertiajs/react'


export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const { flash } = usePage<{ flash: { message: string } }>().props;
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {flash?.message && (
                    <Alert variant="default">
                        <AlertTitle>Success</AlertTitle>
                        <AlertDescription>{flash.message}</AlertDescription>
                    </Alert>
                )}
                {children}
            </AppContent>
        </AppShell>
    );
}
