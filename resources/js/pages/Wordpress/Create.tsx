import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, Server } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];


export default function Create() {
    const [authMethod, setAuthMethod] = useState('password');
    
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        domain: '',
        server_ip: '',
        server_port: 22,
        server_username: 'root',
        server_password: '',
        server_ssh_key: '',
        wp_version: 'latest',
        container_port: 8080,
        db_name: '',
        db_user: '',
        db_password: '',
        db_host: 'mysql',
        ssl_enabled: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/wordpress-sites');
    };

    const generateRandomPassword = () => {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        let password = '';
        for (let i = 0; i < 16; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return password;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create WordPress Site" />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link href={'/wordpress-sites'} className="text-sm text-gray-600 hover:text-gray-900">
                            ← Back to Sites
                        </Link>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div className="p-6 border-b border-gray-200">
                            <h2 className="text-2xl font-bold text-gray-900">Create New WordPress Site</h2>
                            <p className="mt-1 text-sm text-gray-600">Deploy a new WordPress installation on your server</p>
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
                                            placeholder="My Awesome Site"
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
                                            placeholder="example.com"
                                        />
                                        {errors.domain && <p className="mt-1 text-sm text-red-600">{errors.domain}</p>}
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
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Container Port</label>
                                            <input
                                                type="number"
                                                value={data.container_port}
                                                onChange={e => setData('container_port', Number(e.target.value))}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            />
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
                                                placeholder="192.168.1.100"
                                            />
                                            {errors.server_ip && <p className="mt-1 text-sm text-red-600">{errors.server_ip}</p>}
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">SSH Port</label>
                                            <input
                                                type="number"
                                                value={data.server_port}
                                                onChange={e => setData('server_port', Number(e.target.value))}
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
                                        <label className="block text-sm font-medium text-gray-700 mb-2">Authentication Method</label>
                                        <div className="flex space-x-4">
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
                                    </div>

                                    {authMethod === 'password' ? (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Server Password</label>
                                            <input
                                                type="password"
                                                value={data.server_password}
                                                onChange={e => setData('server_password', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            />
                                            {errors.server_password && <p className="mt-1 text-sm text-red-600">{errors.server_password}</p>}
                                        </div>
                                    ) : (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">SSH Private Key</label>
                                            <textarea
                                                value={data.server_ssh_key}
                                                onChange={e => setData('server_ssh_key', e.target.value)}
                                                rows={6}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-xs"
                                                placeholder="-----BEGIN RSA PRIVATE KEY-----"
                                            />
                                            {errors.server_ssh_key && <p className="mt-1 text-sm text-red-600">{errors.server_ssh_key}</p>}
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Database Configuration */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Database Configuration</h3>
                                <div className="grid grid-cols-1 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Database Name</label>
                                        <input
                                            type="text"
                                            value={data.db_name}
                                            onChange={e => setData('db_name', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="wp_database"
                                        />
                                        {errors.db_name && <p className="mt-1 text-sm text-red-600">{errors.db_name}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Database User</label>
                                        <input
                                            type="text"
                                            value={data.db_user}
                                            onChange={e => setData('db_user', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="wp_user"
                                        />
                                        {errors.db_user && <p className="mt-1 text-sm text-red-600">{errors.db_user}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Database Password</label>
                                        <div className="mt-1 flex rounded-md shadow-sm">
                                            <input
                                                type="password"
                                                value={data.db_password}
                                                onChange={e => setData('db_password', e.target.value)}
                                                className="block w-full rounded-l-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => setData('db_password', generateRandomPassword())}
                                                className="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm hover:bg-gray-100"
                                            >
                                                Generate
                                            </button>
                                        </div>
                                        {errors.db_password && <p className="mt-1 text-sm text-red-600">{errors.db_password}</p>}
                                    </div>
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                                <Link
                                    href={'/wordpress-sites'}
                                    className="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {processing ? 'Creating...' : 'Create Site'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}