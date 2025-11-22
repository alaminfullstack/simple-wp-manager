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

interface SitesIndexProps {
    sites: Site[];
}




const SitesIndex = ({ sites }: SitesIndexProps) => {
    const getStatusColor = (status: string) => {
        switch (status) {
            case 'running':
                return 'bg-green-100 text-green-800';
            case 'stopped':
                return 'bg-red-100 text-red-800';
            case 'deploying':
                return 'bg-yellow-100 text-yellow-800';
            case 'failed':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Sites" />
            <div className="container mx-auto px-4 py-8">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold">WordPress Sites</h1>
                    <Link
                        href="/sites/create"
                        className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Add Site
                    </Link>
                </div>
                
                <div className="bg-white shadow-md rounded-lg overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Domain
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Server
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Last Deployed
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {sites.map((site) => (
                                <tr key={site.id}>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="text-sm font-medium text-gray-900">
                                            {site.domain}
                                        </div>
                                        <div className="text-sm text-gray-500">
                                            {site.container_name}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="text-sm text-gray-500">
                                            {site.server.name}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span
                                            className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(
                                                site.status
                                            )}`}
                                        >
                                            {site.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="text-sm text-gray-500">
                                            {site.last_deployed_at
                                                ? new Date(
                                                      site.last_deployed_at
                                                  ).toLocaleString()
                                                : 'Never'}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <Link
                                            href={`/sites/${site.id}`}
                                            className="text-indigo-600 hover:text-indigo-900 mr-3"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            href={`/sites/${site.id}/edit`}
                                            className="text-indigo-600 hover:text-indigo-900 mr-3"
                                        >
                                            Edit
                                        </Link>
                                        {site.status === 'running' ? (
                                            <Link
                                                href={`/sites/${site.id}/stop`}
                                                className="text-yellow-600 hover:text-yellow-900 mr-3"
                                            >
                                                Stop
                                            </Link>
                                        ) : (
                                            <Link
                                                href={`/sites/${site.id}/start`}
                                                className="text-green-600 hover:text-green-900 mr-3"
                                            >
                                                Start
                                            </Link>
                                        )}
                                        <Link
                                            href={`/sites/${site.id}/restart`}
                                            className="text-blue-600 hover:text-blue-900 mr-3"
                                        >
                                            Restart
                                        </Link>
                                        <button
                                            onClick={() => {
                                                if (
                                                    confirm(
                                                        'Are you sure you want to delete this site?'
                                                    )
                                                ) {
                                                    window.location.href = `/sites/${site.id}`;
                                                }
                                            }}
                                            className="text-red-600 hover:text-red-900"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
};

export default SitesIndex;