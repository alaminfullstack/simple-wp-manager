import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, Site, Server } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface SitesEditProps {
    site: Site;
    servers: Server[];
}



const SitesEdit = ({ site, servers }: SitesEditProps) => {
     interface FormData {
        server_id: number;
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

    const { data, setData, put, processing, errors } = useForm<FormData>({
        server_id: site.server_id,
        domain: site.domain,
        container_name: site.container_name,
        database_name: site.database_name,
        database_user: site.database_user,
        database_password: site.database_password, // In a real app, handle this more securely
        admin_username: site.admin_username,
        admin_password: site.admin_password, // Same as above
        admin_email: site.admin_email,
        ssl: site.ssl || { enabled: false },
        firewall_rules: site.firewall_rules || { allow_http: true, allow_https: true, allow_ssh: true },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/sites/${site.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Site: ${site.domain}`} />
            <div className="container mx-auto px-4 py-8">
                <div className="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
                    <h1 className="text-2xl font-bold mb-6">Edit WordPress Site</h1>
                    <form onSubmit={handleSubmit}>
                        {/* Form fields are identical to Create.jsx */}
                        <div className="mb-4">
                            <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="server_id">Server</label>
                            <select id="server_id" className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value={data.server_id} onChange={(e) => setData('server_id', Number(e.target.value))}>
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
                        {/* ... other fields ... */}
                        <div className="flex items-center justify-between">
                            <button type="submit" disabled={processing} className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Update Site
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

export default SitesEdit;