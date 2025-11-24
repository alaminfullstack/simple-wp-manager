import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type ServerListItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Servers',
        href: route('servers.index'),
    },
];

interface ServerEditProps {
    server: ServerListItem & {
        connection_type?: string;
    };
}

export default function Edit({ server }: ServerEditProps) {
    const { data, setData, put, processing, errors } = useForm({
        name: server.name || '',
        ip_address: server.ip_address || '',
        ssh_port: server.ssh_port || 22,
        ssh_user: server.ssh_user || '',
        connection_type: server.connection_type || 'password',
        ssh_password: '',
        ssh_key: '',
    });

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        put(route('servers.update', server.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Server - ${server.name}`} />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 border-b border-gray-200">
                            <div className="flex items-center justify-between">
                                <h2 className="text-2xl font-bold text-gray-800">
                                    Edit Server
                                </h2>
                                <Link
                                    href={route('servers.show', server.id)}
                                    className="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    ← Back to Server
                                </Link>
                            </div>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Server Information */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold text-gray-900">Server Information</h3>

                                <div>
                                    <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                                        Server Name *
                                    </label>
                                    <input
                                        type="text"
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        required
                                    />
                                    {errors.name && (
                                        <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                    )}
                                </div>

                                <div className="grid grid-cols-3 gap-4">
                                    <div className="col-span-2">
                                        <label htmlFor="ip_address" className="block text-sm font-medium text-gray-700">
                                            IP Address *
                                        </label>
                                        <input
                                            type="text"
                                            id="ip_address"
                                            value={data.ip_address}
                                            onChange={(e) => setData('ip_address', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            required
                                        />
                                        {errors.ip_address && (
                                            <p className="mt-1 text-sm text-red-600">{errors.ip_address}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="ssh_port" className="block text-sm font-medium text-gray-700">
                                            SSH Port *
                                        </label>
                                        <input
                                            type="number"
                                            id="ssh_port"
                                            value={data.ssh_port}
                                            onChange={(e) => setData('ssh_port', parseInt(e.target.value))}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            min="1"
                                            max="65535"
                                            required
                                        />
                                        {errors.ssh_port && (
                                            <p className="mt-1 text-sm text-red-600">{errors.ssh_port}</p>
                                        )}
                                    </div>
                                </div>

                                <div>
                                    <label htmlFor="ssh_user" className="block text-sm font-medium text-gray-700">
                                        SSH Username *
                                    </label>
                                    <input
                                        type="text"
                                        id="ssh_user"
                                        value={data.ssh_user}
                                        onChange={(e) => setData('ssh_user', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        required
                                    />
                                    {errors.ssh_user && (
                                        <p className="mt-1 text-sm text-red-600">{errors.ssh_user}</p>
                                    )}
                                </div>
                            </div>

                            {/* Authentication Method */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold text-gray-900">Update Authentication</h3>

                                <div className="flex space-x-4">
                                    <label className="flex items-center">
                                        <input
                                            type="radio"
                                            name="connection_type"
                                            value="password"
                                            checked={data.connection_type === 'password'}
                                            onChange={(e) => setData('connection_type', e.target.value)}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                        />
                                        <span className="ml-2 text-sm font-medium text-gray-700">Password</span>
                                    </label>
                                    <label className="flex items-center">
                                        <input
                                            type="radio"
                                            name="connection_type"
                                            value="key"
                                            checked={data.connection_type === 'key'}
                                            onChange={(e) => setData('connection_type', e.target.value)}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                        />
                                        <span className="ml-2 text-sm font-medium text-gray-700">SSH Key</span>
                                    </label>
                                </div>

                                {data.connection_type === 'password' ? (
                                    <div>
                                        <label htmlFor="ssh_password" className="block text-sm font-medium text-gray-700">
                                            SSH Password
                                        </label>
                                        <input
                                            type="password"
                                            id="ssh_password"
                                            value={data.ssh_password}
                                            onChange={(e) => setData('ssh_password', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="Leave empty to keep current password"
                                        />
                                        {errors.ssh_password && (
                                            <p className="mt-1 text-sm text-red-600">{errors.ssh_password}</p>
                                        )}
                                        <p className="mt-1 text-xs text-gray-500">
                                            Leave empty to keep existing password
                                        </p>
                                    </div>
                                ) : (
                                    <div>
                                        <label htmlFor="ssh_key" className="block text-sm font-medium text-gray-700">
                                            SSH Private Key
                                        </label>
                                        <textarea
                                            id="ssh_key"
                                            value={data.ssh_key}
                                            onChange={(e) => setData('ssh_key', e.target.value)}
                                            rows={8}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"
                                            placeholder="Leave empty to keep current key"
                                        />
                                        {errors.ssh_key && (
                                            <p className="mt-1 text-sm text-red-600">{errors.ssh_key}</p>
                                        )}
                                        <p className="mt-1 text-xs text-gray-500">
                                            Leave empty to keep existing SSH key
                                        </p>
                                    </div>
                                )}
                            </div>

                            {/* Submit Button */}
                            <div className="flex items-center justify-end space-x-4 pt-4 border-t">
                                <Link
                                    href={route('servers.show', server.id)}
                                    className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {processing ? (
                                        <>
                                            <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Updating...
                                        </>
                                    ) : (
                                        <>
                                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Update Server
                                        </>
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>

                    {/* Warning Card */}
                    <div className="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div className="flex">
                            <svg className="h-5 w-5 text-yellow-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div className="ml-3">
                                <h3 className="text-sm font-medium text-yellow-800">Important</h3>
                                <div className="mt-2 text-sm text-yellow-700">
                                    <p>Changing server credentials may affect existing WordPress sites. Test the connection after updating.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}