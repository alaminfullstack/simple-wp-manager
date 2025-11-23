import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Show({ auth, server }) {
    const [testing, setTesting] = useState(false);
    const [installing, setInstalling] = useState(false);

    const getStatusBadge = () => {
        const statusConfig = {
            active: { color: 'bg-green-100 text-green-800', icon: '●' },
            inactive: { color: 'bg-gray-100 text-gray-800', icon: '○' },
            error: { color: 'bg-red-100 text-red-800', icon: '✕' },
        };

        const config = statusConfig[server.status] || statusConfig.inactive;
        
        return (
            <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${config.color}`}>
                <span className="mr-2">{config.icon}</span>
                {server.status.charAt(0).toUpperCase() + server.status.slice(1)}
            </span>
        );
    };

    const testConnection = async () => {
        setTesting(true);
        try {
            const response = await fetch(route('servers.test', server.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('✅ Connection successful!\n\n' + data.message);
                router.reload();
            } else {
                alert('❌ Connection failed!\n\n' + data.message);
            }
        } catch (error) {
            alert('❌ Connection test failed!\n\n' + error.message);
        } finally {
            setTesting(false);
        }
    };

    const installMonitorScript = async () => {
        if (!confirm('This will install the monitoring script on the remote server. Continue?')) {
            return;
        }

        setInstalling(true);
        router.post(route('servers.install-monitor', server.id), {}, {
            onFinish: () => setInstalling(false)
        });
    };

    const handleDelete = () => {
        if (confirm(`Are you sure you want to delete "${server.name}"? This action cannot be undone.`)) {
            router.delete(route('servers.destroy', server.id));
        }
    };

    const getSiteStatusColor = (status) => {
        const colors = {
            running: 'bg-green-100 text-green-800',
            stopped: 'bg-gray-100 text-gray-800',
            deploying: 'bg-blue-100 text-blue-800',
            error: 'bg-red-100 text-red-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`Server - ${server.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <Link
                                        href={route('servers.index')}
                                        className="text-sm text-gray-600 hover:text-gray-900 mb-2 inline-block"
                                    >
                                        ← Back to Servers
                                    </Link>
                                    <h2 className="text-2xl font-bold text-gray-800 flex items-center">
                                        {server.name}
                                        <span className="ml-3">{getStatusBadge()}</span>
                                    </h2>
                                </div>
                                <div className="flex space-x-3">
                                    <button
                                        onClick={testConnection}
                                        disabled={testing}
                                        className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 disabled:opacity-50"
                                    >
                                        {testing ? 'Testing...' : 'Test Connection'}
                                    </button>
                                    <Link
                                        href={route('servers.edit', server.id)}
                                        className="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                                    >
                                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </Link>
                                    <button
                                        onClick={handleDelete}
                                        className="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Server Information */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Server Information</h3>
                                <dl className="divide-y divide-gray-200">
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-gray-500">IP Address</dt>
                                        <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{server.ip_address}</dd>
                                    </div>
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-gray-500">SSH Port</dt>
                                        <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{server.ssh_port}</dd>
                                    </div>
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-gray-500">SSH User</dt>
                                        <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{server.ssh_user}</dd>
                                    </div>
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-gray-500">Auth Method</dt>
                                        <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                            <span className="capitalize">{server.connection_type}</span>
                                        </dd>
                                    </div>
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-gray-500">Last Connected</dt>
                                        <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                            {server.last_connected_at ? new Date(server.last_connected_at).toLocaleString() : 'Never'}
                                        </dd>
                                    </div>
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-gray-500">Created</dt>
                                        <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                            {new Date(server.created_at).toLocaleString()}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        {/* Monitoring */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Monitoring</h3>
                                <p className="text-sm text-gray-600 mb-4">
                                    Install the monitoring script to automatically track container status every 5 minutes.
                                </p>
                                <button
                                    onClick={installMonitorScript}
                                    disabled={installing}
                                    className="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {installing ? (
                                        <>
                                            <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Installing...
                                        </>
                                    ) : (
                                        <>
                                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Install Monitor Script
                                        </>
                                    )}
                                </button>
                                <p className="text-xs text-gray-500 mt-2">
                                    This will install a bash script and cron job on the remote server.
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* WordPress Sites */}
                    <div className="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-semibold text-gray-900">WordPress Sites</h3>
                                <Link
                                    href={route('wordpress.create')}
                                    className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                                >
                                    <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Create Site on This Server
                                </Link>
                            </div>

                            {server.word_press_sites && server.word_press_sites.length > 0 ? (
                                <div className="space-y-3">
                                    {server.word_press_sites.map((site) => (
                                        <div key={site.id} className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                                            <div className="flex items-center justify-between">
                                                <div className="flex-1">
                                                    <div className="flex items-center">
                                                        <h4 className="text-md font-semibold text-gray-900">{site.site_name}</h4>
                                                        <span className={`ml-3 px-2 py-1 text-xs font-medium rounded-full ${getSiteStatusColor(site.status)}`}>
                                                            {site.status}
                                                        </span>
                                                    </div>
                                                    <p className="text-sm text-gray-600 mt-1">
                                                        {site.domain}:{site.port}
                                                    </p>
                                                </div>
                                                <div className="flex space-x-2">
                                                    <Link
                                                        href={route('wordpress.show', site.id)}
                                                        className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                                    >
                                                        View
                                                    </Link>
                                                    {site.status === 'running' && (
                                                        <a
                                                            href={`http://${site.domain}:${site.port}`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="inline-flex items-center px-3 py-2 border border-blue-300 shadow-sm text-sm font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100"
                                                        >
                                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                            </svg>
                                                        </a>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8">
                                    <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">No sites yet</h3>
                                    <p className="mt-1 text-sm text-gray-500">Get started by creating a WordPress site on this server.</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Error Message */}
                    {server.status === 'error' && server.last_error && (
                        <div className="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div className="flex">
                                <svg className="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div className="ml-3">
                                    <h3 className="text-sm font-medium text-red-800">Connection Error</h3>
                                    <div className="mt-2 text-sm text-red-700">
                                        <p>{server.last_error}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}