import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, Wordpress } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];


interface SitesShowProps {
    site: Wordpress;
}

interface InfoRowProps {
    label: string;
    value: string;
    copyable?: boolean;
}

type CopySuccessState = {
    [key: string]: boolean;
};



export default function Show({ site }: SitesShowProps) {
    const [logs, setLogs] = useState('');
    const [loadingLogs, setLoadingLogs] = useState(false);
    const [copySuccess, setCopySuccess] = useState<CopySuccessState>({});
    const [autoRefresh, setAutoRefresh] = useState(site.status === 'deploying');

    const siteUrl = `http://${site.domain}:${site.port}`;
    const adminUrl = `${siteUrl}/wp-admin`;

    // Auto-refresh when deploying
    useEffect(() => {
        if (site.status === 'deploying') {
            const interval = setInterval(() => {
                router.reload({ only: ['site'] });
            }, 5000); // Refresh every 5 seconds

            return () => clearInterval(interval);
        }
    }, [site.status]);

    // Show notification when deployment completes
    useEffect(() => {
        if (autoRefresh && site.status === 'running') {
            setAutoRefresh(false);
            // Could show a toast notification here
        }
    }, [site.status, autoRefresh]);


    const handleStart = () => {
        router.post(`/wordpress/${site.id}/start`);
    };

    const handleStop = () => {
        if (confirm('Are you sure you want to stop this site?')) {
            router.post(`/wordpress/${site.id}/stop`);
        }
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this site? This action cannot be undone.')) {
            router.delete(`/wordpress/${site.id}`);
        }
    };

    const loadLogs = async () => {
        setLoadingLogs(true);
        try {
            const response = await fetch(`/wordpress/${site.id}/logs`);
            const data = await response.json();
            setLogs(data.logs || 'No logs available');
        } catch (error: any) {
            setLogs('Error loading logs: ' + error.message);
        } finally {
            setLoadingLogs(false);
        }
    };

    const copyToClipboard = (text: string, field: string) => {
        navigator.clipboard.writeText(text).then(() => {
            setCopySuccess({ ...copySuccess, [field]: true });
            setTimeout(() => {
                setCopySuccess({ ...copySuccess, [field]: false });
            }, 2000);
        });
    };

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

    const InfoRow = ({ label, value, copyable = false }: InfoRowProps) => (
        <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt className="text-sm font-medium text-gray-500">{label}</dt>
            <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                <div className="flex items-center">
                    <span className="flex-1 break-all">{value}</span>
                    {copyable && (
                        <button
                            onClick={() => copyToClipboard(value, label)}
                            className="ml-2 p-1 text-gray-400 hover:text-gray-600"
                            title="Copy to clipboard"
                        >
                            {copySuccess?.[label] ? (
                                <svg className="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                </svg>
                            ) : (
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            )}
                        </button>
                    )}
                </div>
            </dd>
        </div>
    );


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Site: ${site.site_name}`} />


            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <Link
                                        href={'/wordpress'}
                                        className="text-sm text-gray-600 hover:text-gray-900 mb-2 inline-block"
                                    >
                                        ← Back to Sites
                                    </Link>
                                    <h2 className="text-2xl font-bold text-gray-800 flex items-center">
                                        {site.site_name}
                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(site.status)}`}>
                                            <span className="mr-1">{getStatusIcon(site.status)}</span>
                                            {site.status}
                                        </span>
                                    </h2>
                                </div>
                                <div className="flex space-x-3">
                                    <Link
                                        href={`/wordpress/${site.id}/edit`}
                                        className="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                                    >
                                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </Link>

                                    {site.status === 'stopped' && (
                                        <button
                                            onClick={handleStart}
                                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700"
                                        >
                                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Start
                                        </button>
                                    )}
                                    {site.status === 'running' && (
                                        <button
                                            onClick={handleStop}
                                            className="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700"
                                        >
                                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                                            </svg>
                                            Stop
                                        </button>
                                    )}
                                    <button
                                        onClick={handleDelete}
                                        className="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700"
                                    >
                                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Deployment Status */}
                        {site.status === 'deploying' && (
                            <div className="lg:col-span-2 bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div className="flex">
                                    <svg className="animate-spin h-5 w-5 text-blue-600 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <div className="ml-3 flex-1">
                                        <h3 className="text-sm font-medium text-blue-800">Deployment in Progress</h3>
                                        <div className="mt-2 text-sm text-blue-700">
                                            <p>Your WordPress site is being deployed. This usually takes 1-2 minutes.</p>
                                            <p className="mt-1">This page will automatically refresh. Please wait...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Deletion Status */}
                        {site.status === 'deleting' && (
                            <div className="lg:col-span-2 bg-orange-50 border border-orange-200 rounded-lg p-4">
                                <div className="flex">
                                    <svg className="animate-spin h-5 w-5 text-orange-600 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <div className="ml-3 flex-1">
                                        <h3 className="text-sm font-medium text-orange-800">Deletion in Progress</h3>
                                        <div className="mt-2 text-sm text-orange-700">
                                            <p>Removing containers and cleaning up resources...</p>
                                            <p className="mt-1">You will be redirected shortly.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Quick Access */}
                        {site.status === 'running' && (
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Access</h3>
                                    <div className="space-y-3">
                                        <a
                                            href={siteUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors"
                                        >
                                            <div className="flex items-center">
                                                <svg className="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                                </svg>
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900">Visit Site</p>
                                                    <p className="text-xs text-gray-500">{siteUrl}</p>
                                                </div>
                                            </div>
                                            <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                        <a
                                            href={adminUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors"
                                        >
                                            <div className="flex items-center">
                                                <svg className="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900">Admin Dashboard</p>
                                                    <p className="text-xs text-gray-500">{adminUrl}</p>
                                                </div>
                                            </div>
                                            <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Site Information */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Site Information</h3>
                                <dl className="divide-y divide-gray-200">
                                    <InfoRow label="Container Name" value={site.container_name} copyable />
                                    <InfoRow label="URL" value={siteUrl} copyable />
                                    <InfoRow label="Created" value={site.created_at || 'Not available'} />
                                </dl>
                            </div>
                        </div>

                        {/* Admin Credentials */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg hidden">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Admin Credentials</h3>
                                <dl className="divide-y divide-gray-200">
                                    <InfoRow label="Email" value={site.admin_email} copyable />
                                    <InfoRow label="Username" value={site.admin_user} copyable />
                                    <InfoRow label="Password" value={site.admin_password} copyable />
                                </dl>
                            </div>
                        </div>

                        {/* Database Information */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Database Information</h3>
                                <dl className="divide-y divide-gray-200">
                                    <InfoRow label="Database Name" value={site.db_name} copyable />
                                    <InfoRow label="Database User" value={site.db_user} copyable />
                                    <InfoRow label="Database Password" value={site.db_password} copyable />
                                </dl>
                            </div>
                        </div>
                    </div>

                    {/* Container Logs */}
                    <div className="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-semibold text-gray-900">Container Logs</h3>
                                <button
                                    onClick={loadLogs}
                                    disabled={loadingLogs}
                                    className="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                                >
                                    {loadingLogs ? (
                                        <>
                                            <svg className="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Loading...
                                        </>
                                    ) : (
                                        <>
                                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Refresh Logs
                                        </>
                                    )}
                                </button>
                            </div>
                            {logs ? (
                                <pre className="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto text-xs font-mono max-h-96 overflow-y-auto">
                                    {logs}
                                </pre>
                            ) : (
                                <div className="text-center py-8 text-gray-500">
                                    Click "Refresh Logs" to view container logs
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Error Message */}
                    {site.status === 'error' && site.error_message && (
                        <div className="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div className="flex">
                                <svg className="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div className="ml-3">
                                    <h3 className="text-sm font-medium text-red-800">Error</h3>
                                    <div className="mt-2 text-sm text-red-700">
                                        <p>{site.error_message}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}