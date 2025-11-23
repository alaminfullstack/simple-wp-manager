import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, WordPressSite } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface SitesIndexProps {
    sites: WordPressSite[];
}


export default function Index({ sites }: SitesIndexProps) {
    const getStatusColor = (status: string) => {
        const colors = {
            running: 'bg-green-100 text-green-800',
            stopped: 'bg-gray-100 text-gray-800',
            deploying: 'bg-blue-100 text-blue-800',
            failed: 'bg-red-100 text-red-800',
        };
        return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800';
    };

    const getStatusIcon = (status: string) => {
        const icons = {
            running: '●',
            stopped: '■',
            deploying: '◐',
            failed: '✕',
        };
        return icons[status as keyof typeof icons] || '○';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Wordpress site" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h2 className="text-3xl font-bold text-gray-900">WordPress Sites</h2>
                            <p className="mt-1 text-sm text-gray-600">
                                Manage your WordPress installations across multiple servers
                            </p>
                        </div>
                        <Link
                            href={'/wordpress-sites/create'}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            + New Site
                        </Link>
                    </div>

                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        {[
                            { label: 'Total Sites', value: sites.length, color: 'blue' },
                            { label: 'Running', value: sites.filter(s => s.status === 'running').length, color: 'green' },
                            { label: 'Stopped', value: sites.filter(s => s.status === 'stopped').length, color: 'gray' },
                            { label: 'Failed', value: sites.filter(s => s.status === 'failed').length, color: 'red' },
                        ].map((stat, idx) => (
                            <div key={idx} className="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                                <div className="text-sm font-medium text-gray-500">{stat.label}</div>
                                <div className={`mt-1 text-3xl font-bold text-${stat.color}-600`}>{stat.value}</div>
                            </div>
                        ))}
                    </div>

                    {/* Sites Table */}
                    <div className="bg-white overflow-hidden shadow-sm rounded-lg">
                        {sites.length === 0 ? (
                            <div className="p-12 text-center">
                                <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <h3 className="mt-2 text-sm font-medium text-gray-900">No sites</h3>
                                <p className="mt-1 text-sm text-gray-500">Get started by creating a new WordPress site.</p>
                                <div className="mt-6">
                                    <Link
                                        href={'/wordpress-sites/create'}
                                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                                    >
                                        + New Site
                                    </Link>
                                </div>
                            </div>
                        ) : (
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Server</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Version</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Checked</th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {sites.map((site) => (
                                        <tr key={site.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">{site.name}</div>
                                                        <div className="text-sm text-gray-500">
                                                            <a href={site.url} target="_blank" rel="noopener noreferrer" className="hover:text-blue-600">
                                                                {site.domain}
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(site.status)}`}>
                                                    <span className="mr-1">{getStatusIcon(site.status)}</span>
                                                    {site.status}
                                                </span>
                                                {site.status_message && (
                                                    <div className="text-xs text-gray-500 mt-1">{site.status_message}</div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {site.masked_server_ip}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {site.wp_version}
                                                {site.ssl_enabled && (
                                                    <span className="ml-2 text-green-600" title="SSL Enabled">🔒</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {site.last_checked_at || 'Never'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <Link
                                                    href={`/wordpress-sites/${site.id}`}
                                                    className="text-blue-600 hover:text-blue-900 mr-3"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    href={`/wordpress-sites/${site.id}/edit`}
                                                    className="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Edit
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}