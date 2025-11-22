import React from 'react';
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

interface SitesCreateProps {
    servers: Server[];
}


const SitesCreate = ({ servers }: SitesCreateProps) => {
    interface FormData {
        server_id: string | number;
        domain: string;
        container_name: string;
        database_name: string;
        database_user: string;
        database_password: string;
        admin_username: string;
        admin_password: string;
        admin_email: string;
        ssl: { enabled: boolean };
        firewall_rules: { allow_http: boolean; allow_https: boolean; allow_ssh: boolean };
    }

    const { data, setData, post, processing, errors } = useForm<FormData>({
        server_id: '',
        domain: '',
        container_name: '',
        database_name: '',
        database_user: '',
        database_password: '',
        admin_username: 'admin',
        admin_password: '',
        admin_email: '',
        ssl: { enabled: false },
        firewall_rules: { allow_http: true, allow_https: true, allow_ssh: true },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/sites');
    };


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Site" />

            <div className="container mx-auto px-4 py-8">
                <div className="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
                    <h1 className="text-2xl font-bold mb-6">Add New WordPress Site</h1>
                    <form onSubmit={handleSubmit}>
                        <div className="mb-4">
                            <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="server_id">
                                Server
                            </label>
                            <select
                                id="server_id"
                                className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                value={data.server_id}
                                onChange={(e) => setData('server_id', e.target.value)}
                            >
                                <option value="">Select a Server</option>
                                {servers.map((server) => (
                                    <option key={server.id} value={server.id}>{server.name} ({server.ip_address})</option>
                                ))}
                            </select>
                            {errors.server_id && <div className="text-red-500 text-xs mt-1">{errors.server_id}</div>}
                        </div>

                        <div className="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="domain">Domain</label>
                                <input id="domain" type="text" className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value={data.domain} onChange={(e) => setData('domain', e.target.value)} />
                                {errors.domain && <div className="text-red-500 text-xs mt-1">{errors.domain}</div>}
                            </div>
                            <div>
                                <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="container_name">Container Name</label>
                                <input id="container_name" type="text" className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value={data.container_name} onChange={(e) => setData('container_name', e.target.value)} />
                                {errors.container_name && <div className="text-red-500 text-xs mt-1">{errors.container_name}</div>}
                            </div>
                        </div>

                        <h3 className="text-lg font-semibold text-gray-700 mt-6 mb-4">Database Configuration</h3>
                        <div className="grid grid-cols-3 gap-4 mb-4">
                            <input id="database_name" type="text" placeholder="DB Name" className="shadow appearance-none border rounded py-2 px-3 text-gray-700" value={data.database_name} onChange={(e) => setData('database_name', e.target.value)} />
                            <input id="database_user" type="text" placeholder="DB User" className="shadow appearance-none border rounded py-2 px-3 text-gray-700" value={data.database_user} onChange={(e) => setData('database_user', e.target.value)} />
                            <input id="database_password" type="password" placeholder="DB Password" className="shadow appearance-none border rounded py-2 px-3 text-gray-700" value={data.database_password} onChange={(e) => setData('database_password', e.target.value)} />
                        </div>
                        {errors.database_name && <div className="text-red-500 text-xs mt-1">{errors.database_name}</div>}
                        {errors.database_user && <div className="text-red-500 text-xs mt-1">{errors.database_user}</div>}
                        {errors.database_password && <div className="text-red-500 text-xs mt-1">{errors.database_password}</div>}


                        <h3 className="text-lg font-semibold text-gray-700 mt-6 mb-4">WordPress Admin Account</h3>
                        <div className="grid grid-cols-2 gap-4 mb-4">
                            <input id="admin_username" type="text" placeholder="Admin Username" className="shadow appearance-none border rounded py-2 px-3 text-gray-700" value={data.admin_username} onChange={(e) => setData('admin_username', e.target.value)} />
                            <input id="admin_email" type="email" placeholder="Admin Email" className="shadow appearance-none border rounded py-2 px-3 text-gray-700" value={data.admin_email} onChange={(e) => setData('admin_email', e.target.value)} />
                        </div>
                        <div className="mb-6">
                            <input id="admin_password" type="password" placeholder="Admin Password" className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value={data.admin_password} onChange={(e) => setData('admin_password', e.target.value)} />
                        </div>
                        {errors.admin_username && <div className="text-red-500 text-xs mt-1">{errors.admin_username}</div>}
                        {errors.admin_email && <div className="text-red-500 text-xs mt-1">{errors.admin_email}</div>}
                        {errors.admin_password && <div className="text-red-500 text-xs mt-1">{errors.admin_password}</div>}

                        <h3 className="text-lg font-semibold text-gray-700 mt-6 mb-4">Options</h3>
                        <div className="mb-6">
                            <label className="flex items-center mb-2">
                                <input type="checkbox" className="form-checkbox h-5 w-5 text-indigo-600" checked={data.ssl.enabled} onChange={(e) => setData('ssl', { ...data.ssl, enabled: e.target.checked })} />
                                <span className="ml-2 text-gray-700">Enable SSL</span>
                            </label>
                            <label className="flex items-center">
                                <input type="checkbox" className="form-checkbox h-5 w-5 text-indigo-600" checked={data.firewall_rules.allow_http} onChange={(e) => setData('firewall_rules', { ...data.firewall_rules, allow_http: e.target.checked })} />
                                <span className="ml-2 text-gray-700">Allow HTTP (Port 80)</span>
                            </label>
                            <label className="flex items-center">
                                <input type="checkbox" className="form-checkbox h-5 w-5 text-indigo-600" checked={data.firewall_rules.allow_https} onChange={(e) => setData('firewall_rules', { ...data.firewall_rules, allow_https: e.target.checked })} />
                                <span className="ml-2 text-gray-700">Allow HTTPS (Port 443)</span>
                            </label>
                        </div>

                        <div className="flex items-center justify-between">
                            <button type="submit" disabled={processing} className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Deploy Site
                            </button>
                            <Link href="/sites" className="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                                Cancel
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
};

export default SitesCreate;