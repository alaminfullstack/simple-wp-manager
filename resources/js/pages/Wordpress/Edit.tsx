import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, Wordpress, Server } from '@/types';

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

interface SitesEditProps {
    site: Wordpress;
    servers: Server[];
}

export default function Edit({ site, servers }: SitesEditProps) {
    const [showAdvanced, setShowAdvanced] = useState(false);
    const [generatedPassword, setGeneratedPassword] = useState('');

    const { data, setData, put, processing, errors } = useForm({
        site_name: site.site_name || '',
        server_id: site.server_id || 1,
        domain: site.domain || 'localhost',
        port: site.port || 8080,
        admin_email: site.admin_email || '',
        admin_user: site.admin_user || 'admin',
        admin_password: '',
        db_name: site.db_name || 'wordpress',
        db_user: site.db_user || 'wpuser',
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
        setData('admin_password', password);
    };

    const criticalFieldsChanged = () => {
        return data.port != site.port ||
            data.db_name != site.db_name ||
            data.db_user != site.db_user ||
            (data.db_password && data.db_password != site.db_password);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/wordpress/${site.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Site: ${site.site_name}`} />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 border-b border-gray-200">
                            <div className="flex items-center justify-between">
                                <h2 className="text-2xl font-bold text-gray-800">
                                    Edit WordPress Site
                                </h2>
                                <Link
                                    href={`/wordpress/${site.id}`}
                                    className="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    ← Back to Site
                                </Link>
                            </div>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Deployment Location (Read Only) */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold text-gray-900">Deployment Location</h3>
                                <div className="bg-gray-50 p-4 rounded-md border border-gray-200">
                                    <div className="flex items-center text-sm text-gray-600">
                                        <svg className="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                        </svg>
                                        <span className="font-medium mr-2">Server:</span>
                                        {site.server ? (
                                            <span>{site.server.name} ({site.server.ip_address})</span>
                                        ) : (
                                            <span>Local (Docker Desktop)</span>
                                        )}
                                    </div>
                                </div>
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
                                            onChange={(e) => setData('port', Number(e.target.value))}
                                            className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                            placeholder="8080"
                                            min="1024"
                                            max="65535"
                                        />
                                        {errors.port && (
                                            <p className="mt-1 text-sm text-red-600">{errors.port}</p>
                                        )}
                                        {data.port != site.port && (
                                            <p className="mt-1 text-xs text-yellow-600">
                                                ⚠️ Changing port will restart the site
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Admin Information */}
                            <div className="space-y-4 hidden">
                                <h3 className="text-lg font-semibold text-gray-900">Admin Account</h3>

                                <div>
                                    <label htmlFor="admin_email" className="block text-gray-700 text-sm font-bold mb-2">
                                        Admin Email *
                                    </label>
                                    <input
                                        type="email"
                                        id="admin_email"
                                        value={data.admin_email}
                                        onChange={(e) => setData('admin_email', e.target.value)}
                                        className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                        placeholder="admin@example.com"
                                        required
                                    />
                                    {errors.admin_email && (
                                        <p className="mt-1 text-sm text-red-600">{errors.admin_email}</p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="admin_user" className="block text-gray-700 text-sm font-bold mb-2">
                                        Admin Username *
                                    </label>
                                    <input
                                        type="text"
                                        id="admin_user"
                                        value={data.admin_user}
                                        onChange={(e) => setData('admin_user', e.target.value)}
                                        className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                        placeholder="admin"
                                        required
                                    />
                                    {errors.admin_user && (
                                        <p className="mt-1 text-sm text-red-600">{errors.admin_user}</p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="admin_password" className="block text-gray-700 text-sm font-bold mb-2">
                                        Admin Password
                                    </label>
                                    <div className="mt-1 flex rounded-md shadow-sm">
                                        <input
                                            type="text"
                                            id="admin_password"
                                            value={data.admin_password}
                                            onChange={(e) => setData('admin_password', e.target.value)}
                                            className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                            placeholder="Leave empty to keep current"
                                            minLength={8}
                                        />
                                        <button
                                            type="button"
                                            onClick={generatePassword}
                                            className="inline-flex items-center px-4 py-2 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 text-gray-700 text-sm hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                                            Generate
                                        </button>
                                    </div>
                                    {errors.admin_password && (
                                        <p className="mt-1 text-sm text-red-600">{errors.admin_password}</p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500">
                                        Current: {site.admin_password}
                                    </p>
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
                                        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                            <div className="flex">
                                                <svg className="h-5 w-5 text-yellow-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <div className="ml-3">
                                                    <h3 className="text-sm font-medium text-yellow-800">Warning</h3>
                                                    <div className="mt-2 text-sm text-yellow-700">
                                                        <p>Changing database settings will restart the site and may cause data loss if not done properly.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

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
                                            <p className="mt-1 text-xs text-gray-500">
                                                Current: {site.db_name}
                                            </p>
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
                                            <p className="mt-1 text-xs text-gray-500">
                                                Current: {site.db_user}
                                            </p>
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
                                                placeholder="Leave empty to keep current"
                                            />
                                            {errors.db_password && (
                                                <p className="mt-1 text-sm text-red-600">{errors.db_password}</p>
                                            )}
                                            <p className="mt-1 text-xs text-gray-500">
                                                Current: {site.db_password}
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Warning if critical changes */}
                            {criticalFieldsChanged() && (
                                <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <div className="flex">
                                        <svg className="h-5 w-5 text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <div className="ml-3">
                                            <h3 className="text-sm font-medium text-red-800">Site Restart Required</h3>
                                            <div className="mt-2 text-sm text-red-700">
                                                <p>You've changed critical settings (port or database configuration). The site will be stopped and restarted with new containers. This may take 1-2 minutes.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Submit Button */}
                            <div className="flex items-center justify-end space-x-4 pt-4 border-t">
                                <Link
                                    href={`/wordpress/${site.id}`}
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
                                            Updating...
                                        </>
                                    ) : (
                                        <>
                                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Update WordPress Site
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
                                <h3 className="text-sm font-medium text-blue-800">Update Tips</h3>
                                <div className="mt-2 text-sm text-blue-700 space-y-1">
                                    <p>• Leave password fields empty to keep current passwords</p>
                                    <p>• Changing port, database name, or credentials requires site restart</p>
                                    <p>• Site name and admin info can be updated without restart</p>
                                    <p>• Current values are shown below database fields for reference</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}