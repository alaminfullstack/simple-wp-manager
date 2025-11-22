import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Server } from '../../types';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface ServersShowProps {
    server: Server & { sites: any[] }; // Using `any[]` for sites for simplicity here
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];


const ServersShow = ({ server }: ServersShowProps) => {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Server: ${server.name}`} />
    
            <div className="container mx-auto px-4 py-8">
                <div className="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
                    <div className="flex justify-between items-center mb-6">
                        <h1 className="text-2xl font-bold">{server.name}</h1>
                        <div>
                            <Link
                                href={`/servers/${server.id}/edit`}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded mr-2"
                            >
                                Edit
                            </Link>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <h3 className="text-lg font-semibold text-gray-700 mb-2">Connection Details</h3>
                            <p><span className="font-medium">IP Address:</span> {server.ip_address}</p>
                            <p><span className="font-medium">Port:</span> {server.port}</p>
                            <p><span className="font-medium">Username:</span> {server.username}</p>
                            <p><span className="font-medium">Path:</span> {server.path}</p>
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold text-gray-700 mb-2">Status</h3>
                            <p>
                                <span className="font-medium">Active:</span>
                                <span className={`ml-2 px-2 py-1 text-xs rounded-full ${server.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                    {server.active ? 'Yes' : 'No'}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div className="mt-8">
                        <h3 className="text-lg font-semibold text-gray-700 mb-4">Sites on this Server ({server.sites.length})</h3>
                        {server.sites.length > 0 ? (
                            <div className="bg-white shadow overflow-hidden sm:rounded-md">
                                <ul className="divide-y divide-gray-200">
                                    {server.sites.map(site => (
                                        <li key={site.id}>
                                            <Link href={`/sites/${site.id}`} className="block hover:bg-gray-50">
                                                <div className="px-4 py-4 sm:px-6">
                                                    <div className="flex items-center justify-between">
                                                        <p className="text-sm font-medium text-indigo-600 truncate">{site.domain}</p>
                                                        <div className="ml-2 flex-shrink-0 flex">
                                                            <p className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                                                site.status === 'running' ? 'bg-green-100 text-green-800' :
                                                                site.status === 'stopped' ? 'bg-red-100 text-red-800' :
                                                                site.status === 'deploying' ? 'bg-yellow-100 text-yellow-800' :
                                                                'bg-gray-100 text-gray-800'
                                                            }`}>
                                                                {site.status}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ) : (
                            <p className="text-gray-500">No sites are currently associated with this server.</p>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
};

export default ServersShow;