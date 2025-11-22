import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, Site } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];


interface SitesShowProps {
    site: Site;
}


const SitesShow = ({ site }: SitesShowProps) => {
    const getStatusColor = (status: string) => {
        switch (status) {
            case 'running': return 'bg-green-100 text-green-800';
            case 'stopped': return 'bg-red-100 text-red-800';
            case 'deploying': return 'bg-yellow-100 text-yellow-800';
            case 'failed': return 'bg-red-100 text-red-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Site: ${site.domain}`} />
       
            <div className="container mx-auto px-4 py-8">
                <div className="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
                    <div className="flex justify-between items-center mb-6">
                        <h1 className="text-2xl font-bold">{site.domain}</h1>
                        <div>
                            <Link href={`/sites/${site.id}/edit`} className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded mr-2">
                                Edit
                            </Link>
                        </div>
                    </div>

                    <div className="mb-6">
                        <span className={`px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(site.status)}`}>
                            {site.status.toUpperCase()}
                        </span>
                        <p className="text-sm text-gray-500 mt-2">Last deployed: {site.last_deployed_at ? new Date(site.last_deployed_at).toLocaleString() : 'Never'}</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 className="text-lg font-semibold text-gray-700 mb-4">Site Details</h3>
                            <p><span className="font-medium">Domain:</span> {site.domain}</p>
                            <p><span className="font-medium">Container:</span> {site.container_name}</p>
                            <p><span className="font-medium">Server:</span> {site.server.name} ({site.server.ip_address})</p>
                            <p><span className="font-medium">Admin Email:</span> {site.admin_email}</p>
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold text-gray-700 mb-4">Database Details</h3>
                            <p><span className="font-medium">Database Name:</span> {site.database_name}</p>
                            <p><span className="font-medium">Database User:</span> {site.database_user}</p>
                        </div>
                    </div>

                    <div className="mt-8 border-t pt-6">
                        <h3 className="text-lg font-semibold text-gray-700 mb-4">Container Controls</h3>
                        <div className="flex space-x-4">
                            {site.status === 'running' ? (
                                <Link href={`/sites/${site.id}/stop`} method="get" as="button" type="button" className="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                    Stop
                                </Link>
                            ) : (
                                <Link href={`/sites/${site.id}/start`} method="get" as="button" type="button" className="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Start
                                </Link>
                            )}
                            <Link href={`/sites/${site.id}/restart`} method="get" as="button" type="button" className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Restart
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
};

export default SitesShow;