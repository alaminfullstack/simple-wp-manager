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
    {
        title: 'WordPress Sites',
        href: '/wordpress',
    },
];

interface ServerProps {
    servers: Server[];
}

export default function Create({ servers }: ServerProps) {
    const [showAdvanced, setShowAdvanced] = useState(false);
    const [generatedPassword, setGeneratedPassword] = useState('');

    const { data, setData, post, processing, errors } = useForm({
        server_id: '',
        site_name: '',
        domain: 'localhost',
        port: 8080,
        // admin_email: '',
        // admin_user: 'admin',
        // admin_password: '',
        db_name: 'wordpress',
        db_user: 'wpuser',
        db_password: '',
    });

    const generatePassword = () => {
        const length = 16;
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
        let password = "";
        for (let i = 0; i < length; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        setGeneratedPassword(password);
        // setData('admin_password', password);
    };

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(`/wordpress`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create WordPress Site" />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 border-b border-gray-200">
                            <div className="flex items-center justify-between">
                                <h2 className="text-2xl font-bold text-gray-800">
                                    Create New WordPress Site
                                </h2>
                                <Link
                                    href={`/wordpress`}
                                    className="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    ← Back to Sites
                                </Link>
                            </div>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Server Selection */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold text-gray-900">Deployment Location</h3>

                                <div>
                                    <label htmlFor="server_id" className="block text-gray-700 text-sm font-bold mb-2">
                                        Server
                                    </label>
                                    <select
                                        id="server_id"
                                        value={data.server_id}
                                        onChange={(e) => setData('server_id', e.target.value)}
                                        className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                    >
                                        <option value="">Local (This Machine)</option>
                                        {servers && servers.map((server) => (
                                            <option key={server.id} value={server.id}>
                                                {server.name} ({server.ip_address})
                                            </option>
                                        ))}
                                    </select>
                                    {errors.server_id && (
                                        <p className="mt-1 text-sm text-red-600">{errors.server_id}</p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500">
                                        {data.server_id ? 'Site will be deployed to the selected remote server' : 'Site will be deployed locally using Docker Desktop'}
                                    </p>
                                </div>

                                {!servers || servers.length === 0 ? (
                                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                        <p className="text-sm text-blue-700">
                                            No remote servers configured. <Link href={`/servers/create`} className="font-medium underline">Add a server</Link> to deploy remotely.
                                        </p>
                                    </div>
                                ) : null}
                            </div>

                            {/* Basic Information */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold text-gray-900">Basic Information</h3>

                                <div>
                                    <label htmlFor="site_name" className="block text-gray-700 text-sm font-bold mb-2">
                                        Site Name *
                                    </label>
                                    <input
                                        type="text"
                                        id="site_name"
                                        value={data.site_name}
                                        onChange={(e) => setData('site_name', e.target.value)}
                                        className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                        placeholder="My WordPress Site"
                                        required
                                    />
                                    {errors.site_name && (
                                        <p className="mt-1 text-sm text-red-600">{errors.site_name}</p>
                                    )}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label htmlFor="domain" className="block text-gray-700 text-sm font-bold mb-2">
                                            Domain
                                        </label>
                                        <input
                                            type="text"
                                            id="domain"
                                            value={data.domain}
                                            onChange={(e) => setData('domain', e.target.value)}
                                            className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                            placeholder="localhost"
                                        />
                                        {errors.domain && (
                                            <p className="mt-1 text-sm text-red-600">{errors.domain}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="port" className="block text-gray-700 text-sm font-bold mb-2">
                                            Port
                                        </label>
                                        <input
                                            type="number"
                                            id="port"
                                            value={data.port}
                                            onChange={(e) => setData('port', parseInt(e.target.value))}
                                            className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                            placeholder="8080"
                                            min="1024"
                                            max="65535"
                                        />
                                        {errors.port && (
                                            <p className="mt-1 text-sm text-red-600">{errors.port}</p>
                                        )}
                                    </div>
                                </div>
                            </div>


                            {/* Advanced Settings */}
                            <div className="space-y-4">
                                <button
                                    type="button"
                                    onClick={() => setShowAdvanced(!showAdvanced)}
                                    className="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900"
                                >
                                    <svg
                                        className={`w-5 h-5 mr-2 transition-transform ${showAdvanced ? 'rotate-90' : ''}`}
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                    Advanced Database Settings
                                </button>

                                {showAdvanced && (
                                    <div className="space-y-4 pl-7">
                                        <div>
                                            <label htmlFor="db_name" className="block text-gray-700 text-sm font-bold mb-2">
                                                Database Name
                                            </label>
                                            <input
                                                type="text"
                                                id="db_name"
                                                value={data.db_name}
                                                onChange={(e) => setData('db_name', e.target.value)}
                                                className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                                placeholder="wordpress"
                                            />
                                            {errors.db_name && (
                                                <p className="mt-1 text-sm text-red-600">{errors.db_name}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label htmlFor="db_user" className="block text-gray-700 text-sm font-bold mb-2">
                                                Database User
                                            </label>
                                            <input
                                                type="text"
                                                id="db_user"
                                                value={data.db_user}
                                                onChange={(e) => setData('db_user', e.target.value)}
                                                className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                                placeholder="wpuser"
                                            />
                                            {errors.db_user && (
                                                <p className="mt-1 text-sm text-red-600">{errors.db_user}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label htmlFor="db_password" className="block text-gray-700 text-sm font-bold mb-2">
                                                Database Password
                                            </label>
                                            <input
                                                type="text"
                                                id="db_password"
                                                value={data.db_password}
                                                onChange={(e) => setData('db_password', e.target.value)}
                                                className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                                placeholder="Auto-generated if empty"
                                            />
                                            {errors.db_password && (
                                                <p className="mt-1 text-sm text-red-600">{errors.db_password}</p>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Submit Button */}
                            <div className="flex items-center justify-end space-x-4 pt-4 border-t">
                                <Link
                                    href={`/wordpress`}
                                    className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                >
                                    {processing ? (
                                        <>
                                            <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Creating Site...
                                        </>
                                    ) : (
                                        <>
                                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Create WordPress Site
                                        </>
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>

                    {/* Info Card */}
                    <div className="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div className="flex">
                            <svg className="h-5 w-5 text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div className="ml-3">
                                <h3 className="text-sm font-medium text-blue-800">Note</h3>
                                <div className="mt-2 text-sm text-blue-700 space-y-1">
                                    {data.server_id ? (
                                        <>
                                            <p>• Site will be deployed to the selected remote server</p>
                                            <p>• Your local Docker Desktop does not need to be running</p>
                                            <p>• Deployment will happen via SSH on the remote server</p>
                                            <p>• This may take 2-3 minutes</p>
                                        </>
                                    ) : (
                                        <>
                                            <p>• Site will be deployed locally using Docker Desktop</p>
                                            <p>• <strong>Docker Desktop must be running</strong> on your computer</p>
                                            <p>• Site will be accessible at http://{data.domain}:{data.port}</p>
                                            <p>• Deployment may take 1-2 minutes</p>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}