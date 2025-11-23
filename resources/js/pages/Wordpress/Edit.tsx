import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, Site, Server, WordPressSite } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface SitesEditProps {
    site: WordPressSite;
}


export default function Edit({ site }: SitesEditProps) {
    const [authMethod, setAuthMethod] = useState('password');
    
    const { data, setData, put, processing, errors } = useForm({
        name: site.name || '',
        domain: site.domain || '',
        server_ip: site.server_ip || '',
        server_port: site.server_port || 22,
        server_username: site.server_username || '',
        server_password: '',
        server_ssh_key: '',
        wp_version: site.wp_version || 'latest',
        container_port: site.container_port || 8080,
        ssl_enabled: site.ssl_enabled || false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/wordpress-sites/${site.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Site: ${site.name}`} />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link href={`/wordpress-sites/${site.id}`} className="text-sm text-gray-600 hover:text-gray-900">
                            ← Back to Site
                        </Link>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div className="p-6 border-b border-gray-200">
                            <h2 className="text-2xl font-bold text-gray-900">Edit WordPress Site</h2>
                            <p className="mt-1 text-sm text-gray-600">Update your WordPress site configuration</p>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Site Information */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Site Information</h3>
                                <div className="grid grid-cols-1 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Site Name</label>
                                        <input
                                            type="text"
                                            value={data.name}
                                            onChange={e => setData('name', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Domain</label>
                                        <input
                                            type="text"
                                            value={data.domain}
                                            onChange={e => setData('domain', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        {errors.domain && <p className="mt-1 text-sm text-red-600">{errors.domain}</p>}
                                        <p className="mt-1 text-xs text-amber-600">⚠️ Changing domain will trigger redeployment</p>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">WordPress Version</label>
                                            <input
                                                type="text"
                                                value={data.wp_version}
                                                onChange={e => setData('wp_version', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            />
                                            <p className="mt-1 text-xs text-amber-600">⚠️ Changing version will trigger redeployment</p>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Container Port</label>
                                            <input
                                                type="number"
                                                value={data.container_port}
                                                onChange={e => setData('container_port', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            />
                                            <p className="mt-1 text-xs text-amber-600">⚠️ Changing port will trigger redeployment</p>
                                        </div>
                                    </div>

                                    <div className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={data.ssl_enabled}
                                            onChange={e => setData('ssl_enabled', e.target.checked)}
                                            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <label className="ml-2 block text-sm text-gray-700">Enable SSL</label>
                                    </div>
                                </div>
                            </div>

                            {/* Server Configuration */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Server Configuration</h3>
                                <div className="grid grid-cols-1 gap-4">
                                    <div className="grid grid-cols-3 gap-4">
                                        <div className="col-span-2">
                                            <label className="block text-sm font-medium text-gray-700">Server IP Address</label>
                                            <input
                                                type="text"
                                                value={data.server_ip}
                                                onChange={e => setData('server_ip', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            />
                                            {errors.server_ip && <p className="mt-1 text-sm text-red-600">{errors.server_ip}</p>}
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">SSH Port</label>
                                            <input
                                                type="number"
                                                value={data.server_port}
                                                onChange={e => setData('server_port', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">SSH Username</label>
                                        <input
                                            type="text"
                                            value={data.server_username}
                                            onChange={e => setData('server_username', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">Update Authentication (Optional)</label>
                                        <div className="flex space-x-4 mb-3">
                                            <button
                                                type="button"
                                                onClick={() => setAuthMethod('password')}
                                                className={`px-4 py-2 rounded-md ${authMethod === 'password' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                                            >
                                                Password
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setAuthMethod('ssh_key')}
                                                className={`px-4 py-2 rounded-md ${authMethod === 'ssh_key' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                                            >
                                                SSH Key
                                            </button>
                                        </div>

                                        {authMethod === 'password' ? (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">New Server Password</label>
                                                <input
                                                    type="password"
                                                    value={data.server_password}
                                                    onChange={e => setData('server_password', e.target.value)}
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="Leave blank to keep current"
                                                />
                                                {errors.server_password && <p className="mt-1 text-sm text-red-600">{errors.server_password}</p>}
                                            </div>
                                        ) : (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">New SSH Private Key</label>
                                                <textarea
                                                    value={data.server_ssh_key}
                                                    onChange={e => setData('server_ssh_key', e.target.value)}
                                                    rows={6}
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-xs"
                                                    placeholder="Leave blank to keep current"
                                                />
                                                {errors.server_ssh_key && <p className="mt-1 text-sm text-red-600">{errors.server_ssh_key}</p>}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                                <Link
                                    href={`/wordpress/${site.id}`}
                                    className="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {processing ? 'Updating...' : 'Update Site'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}