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

interface ServersEditProps {
    server: Server;
}


const ServersEdit = ({ server }: ServersEditProps) => {
    interface FormData {
        name: string;
        ip_address: string;
        port: number;
        username: string;
        private_key: string;
        password: string;
        path: string;
        active: boolean;
    }

    const { data, setData, put, processing, errors } = useForm<FormData>({
        name: server.name,
        ip_address: server.ip_address,
        port: server.port,
        username: server.username,
        private_key: server.private_key || '',
        password: '',
        path: server.path,
        active: server.active,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/servers/${server.id}`);
    };
    
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Server: ${server.name}`} />
            

            <div className="container mx-auto px-4 py-8">
                <div className="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
                    <h1 className="text-2xl font-bold mb-6">Edit Server</h1>
                    <form onSubmit={handleSubmit}>
                        {/* Form fields are identical to Create.jsx */}
                        <div className="mb-4">
                            <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="name">Name</label>
                            <input id="name" type="text" className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                            {errors.name && <div className="text-red-500 text-xs mt-1">{errors.name}</div>}
                        </div>
                        <div className="mb-4">
                            <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="ip_address">IP Address</label>
                            <input id="ip_address" type="text" className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value={data.ip_address} onChange={(e) => setData('ip_address', e.target.value)} />
                            {errors.ip_address && <div className="text-red-500 text-xs mt-1">{errors.ip_address}</div>}
                        </div>
                        <div className="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="port">SSH Port</label>
                                <input id="port" type="number" className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value={data.port} onChange={(e) => setData('port', Number(e.target.value))} />
                                {errors.port && <div className="text-red-500 text-xs mt-1">{errors.port}</div>}
                            </div>
                            <div>
                                <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="username">Username</label>
                                <input id="username" type="text" className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value={data.username} onChange={(e) => setData('username', e.target.value)} />
                                {errors.username && <div className="text-red-500 text-xs mt-1">{errors.username}</div>}
                            </div>
                        </div>
                        <div className="mb-4">
                            <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="private_key">Private Key</label>
                            <textarea id="private_key" className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" rows={5} value={data.private_key} onChange={(e) => setData('private_key', e.target.value)} placeholder="Leave empty to keep existing key"></textarea>
                            {errors.private_key && <div className="text-red-500 text-xs mt-1">{errors.private_key}</div>}
                        </div>
                        <div className="mb-4">
                            <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="password">Password</label>
                            <input id="password" type="password" className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value={data.password} onChange={(e) => setData('password', e.target.value)} placeholder="Enter new password to change"/>
                            {errors.password && <div className="text-red-500 text-xs mt-1">{errors.password}</div>}
                        </div>
                        <div className="mb-6">
                            <label className="flex items-center">
                                <input type="checkbox" className="form-checkbox h-5 w-5 text-indigo-600" checked={data.active} onChange={(e) => setData('active', e.target.checked)} />
                                <span className="ml-2 text-gray-700">Active</span>
                            </label>
                        </div>
                        <div className="flex items-center justify-between">
                            <button type="submit" disabled={processing} className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Update Server
                            </button>
                            <Link href="/servers" className="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                                Cancel
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
};

export default ServersEdit;