import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, WordPressSite } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];


interface SitesShowProps {
    site: WordPressSite;
}

export default function Show({ site }: SitesShowProps) {
    const getStatusColor = (status: string) => {
        const colors = {
            running: 'bg-green-100 text-green-800',
            stopped: 'bg-gray-100 text-gray-800',
            deploying: 'bg-blue-100 text-blue-800',
            failed: 'bg-red-100 text-red-800',
        };
        return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800';
    };

    const handleAction = (action: string) => {
        if (confirm(`Are you sure you want to ${action} this site?`)) {
            // router.post(route(`sites.${action}`, site.id));
            router.post(`/wordpress-sites/${site.id}/${action}`)
        }
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this site? This action cannot be undone.')) {
            router.delete(`/wordpress-sites/${site.id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Site: ${site.name}`} />
               

            <div className="py-12">
                <div className="max-w-5xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link href={`/wordpress/${site.id}`} className="text-sm text-gray-600 hover:text-gray-900">
                            ← Back to Sites
                        </Link>
                    </div>

                    {/* Header with Actions */}
                    <div className="bg-white overflow-hidden shadow-sm rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex items-start justify-between">
                                <div>
                                    <h1 className="text-3xl font-bold text-gray-900">{site.name}</h1>
                                    <p className="mt-1 text-sm text-gray-500">
                                        <a href={site.url} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:text-blue-800">
                                            {site.domain} ↗
                                        </a>
                                    </p>
                                    <div className="mt-3">
                                        <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(site.status)}`}>
                                            {site.status}
                                        </span>
                                    </div>
                                </div>
                                <div className="flex space-x-2">
                                    <Link
                                        href={`/wordpress-sites/${site.id}/edit`}
                                        className="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Edit
                                    </Link>
                                    {site.status === 'running' && (
                                        <>
                                            <button
                                                onClick={() => handleAction('restart')}
                                                className="px-4 py-2 border border-blue-300 rounded-md text-sm font-medium text-blue-700 hover:bg-blue-50"
                                            >
                                                Restart
                                            </button>
                                            <button
                                                onClick={() => handleAction('stop')}
                                                className="px-4 py-2 border border-yellow-300 rounded-md text-sm font-medium text-yellow-700 hover:bg-yellow-50"
                                            >
                                                Stop
                                            </button>
                                        </>
                                    )}
                                    {site.status === 'stopped' && (
                                        <button
                                            onClick={() => handleAction('start')}
                                            className="px-4 py-2 border border-green-300 rounded-md text-sm font-medium text-green-700 hover:bg-green-50"
                                        >
                                            Start
                                        </button>
                                    )}
                                    <button
                                        onClick={handleDelete}
                                        className="px-4 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 hover:bg-red-50"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>

                            {site.status_message && (
                                <div className="mt-4 p-3 bg-gray-50 rounded-md">
                                    <p className="text-sm text-gray-700">{site.status_message}</p>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Site Configuration */}
                        <div className="bg-white overflow-hidden shadow-sm rounded-lg">
                            <div className="p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Site Configuration</h2>
                                <dl className="space-y-3">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Container Name</dt>
                                        <dd className="mt-1 text-sm text-gray-900 font-mono">{site.container_name}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">WordPress Version</dt>
                                        <dd className="mt-1 text-sm text-gray-900">{site.wp_version}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Container Port</dt>
                                        <dd className="mt-1 text-sm text-gray-900">{site.container_port}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">SSL Status</dt>
                                        <dd className="mt-1 text-sm text-gray-900">
                                            {site.ssl_enabled ? (
                                                <span className="text-green-600">✓ Enabled</span>
                                            ) : (
                                                <span className="text-gray-500">✗ Disabled</span>
                                            )}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Last Checked</dt>
                                        <dd className="mt-1 text-sm text-gray-900">{site.last_checked_at || 'Never'}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        {/* Server Configuration */}
                        <div className="bg-white overflow-hidden shadow-sm rounded-lg">
                            <div className="p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Server Configuration</h2>
                                <dl className="space-y-3">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Server IP</dt>
                                        <dd className="mt-1 text-sm text-gray-900 font-mono">{site.server_ip}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">SSH Port</dt>
                                        <dd className="mt-1 text-sm text-gray-900">{site.server_port}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">SSH Username</dt>
                                        <dd className="mt-1 text-sm text-gray-900">{site.server_username}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        {/* Database Configuration */}
                        <div className="bg-white overflow-hidden shadow-sm rounded-lg">
                            <div className="p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Database Configuration</h2>
                                <dl className="space-y-3">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Database Name</dt>
                                        <dd className="mt-1 text-sm text-gray-900 font-mono">{site.db_name}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Database User</dt>
                                        <dd className="mt-1 text-sm text-gray-900 font-mono">{site.db_user}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Database Host</dt>
                                        <dd className="mt-1 text-sm text-gray-900 font-mono">{site.db_host}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        {/* Timeline */}
                        <div className="bg-white overflow-hidden shadow-sm rounded-lg">
                            <div className="p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Timeline</h2>
                                <dl className="space-y-3">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Created</dt>
                                        <dd className="mt-1 text-sm text-gray-900">{site.created_at}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Last Updated</dt>
                                        <dd className="mt-1 text-sm text-gray-900">{site.updated_at}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}