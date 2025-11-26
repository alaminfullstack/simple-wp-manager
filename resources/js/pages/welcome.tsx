import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Welcome">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700"
                    rel="stylesheet"
                />
            </Head>
            <div className="flex min-h-screen flex-col bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
                {/* Navigation */}
                <header className="w-full border-b border-gray-200/50 bg-white/80 backdrop-blur-sm dark:border-gray-700/50 dark:bg-gray-900/80">
                    <nav className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
                        <div className="flex items-center gap-2">
                            <svg className="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                            </svg>
                            <span className="text-xl font-bold text-gray-900 dark:text-white">WP Manager</span>
                        </div>
                        <div className="flex items-center gap-4">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                                >
                                    Go to Dashboard
                                    <svg className="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition-colors"
                                    >
                                        Log in
                                    </Link>
                                    {canRegister && (
                                        <Link
                                            href={register()}
                                            className="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                                        >
                                            Get Started
                                        </Link>
                                    )}
                                </>
                            )}
                        </div>
                    </nav>
                </header>

                {/* Hero Section */}
                <main className="flex-1">
                    <div className="mx-auto max-w-7xl px-6 py-16 sm:py-24 lg:px-8">
                        <div className="text-center">
                            <h1 className="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl dark:text-white">
                                Manage WordPress Sites
                                <span className="block text-blue-600 dark:text-blue-400">Effortlessly</span>
                            </h1>
                            <p className="mx-auto mt-6 max-w-2xl text-lg leading-8 text-gray-600 dark:text-gray-300">
                                Deploy, manage, and monitor multiple WordPress sites from a single dashboard. 
                                Support for both local and remote servers with Docker.
                            </p>
                            <div className="mt-10 flex items-center justify-center gap-x-6">
                                {auth.user ? (
                                    <Link
                                        href={dashboard()}
                                        className="rounded-lg bg-blue-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition-all"
                                    >
                                        Go to Dashboard
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={register()}
                                            className="rounded-lg bg-blue-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition-all"
                                        >
                                            Get Started Free
                                        </Link>
                                        <Link
                                            href={login()}
                                            className="text-base font-semibold leading-6 text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                        >
                                            Sign in <span aria-hidden="true">→</span>
                                        </Link>
                                    </>
                                )}
                            </div>
                        </div>

                        {/* Features Grid */}
                        <div className="mx-auto mt-24 max-w-7xl">
                            <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                                {/* Feature 1 */}
                                <div className="relative rounded-2xl border border-gray-200 bg-white p-8 shadow-sm hover:shadow-md transition-shadow dark:border-gray-700 dark:bg-gray-800">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-600">
                                        <svg className="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <h3 className="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                                        Quick Deployment
                                    </h3>
                                    <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        Deploy WordPress sites in minutes with automated Docker containers and pre-configured settings.
                                    </p>
                                </div>

                                {/* Feature 2 */}
                                <div className="relative rounded-2xl border border-gray-200 bg-white p-8 shadow-sm hover:shadow-md transition-shadow dark:border-gray-700 dark:bg-gray-800">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-600">
                                        <svg className="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                        </svg>
                                    </div>
                                    <h3 className="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                                        Remote Servers
                                    </h3>
                                    <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        Connect and manage multiple remote servers via SSH. Deploy sites anywhere with ease.
                                    </p>
                                </div>

                                {/* Feature 3 */}
                                <div className="relative rounded-2xl border border-gray-200 bg-white p-8 shadow-sm hover:shadow-md transition-shadow dark:border-gray-700 dark:bg-gray-800">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-green-600">
                                        <svg className="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                    <h3 className="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                                        Real-time Monitoring
                                    </h3>
                                    <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        Monitor site status, performance, and logs in real-time from your dashboard.
                                    </p>
                                </div>

                                {/* Feature 4 */}
                                <div className="relative rounded-2xl border border-gray-200 bg-white p-8 shadow-sm hover:shadow-md transition-shadow dark:border-gray-700 dark:bg-gray-800">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-600">
                                        <svg className="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <h3 className="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                                        Docker Powered
                                    </h3>
                                    <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        Isolated environments for each site with Docker containers for maximum security and flexibility.
                                    </p>
                                </div>

                                {/* Feature 5 */}
                                <div className="relative rounded-2xl border border-gray-200 bg-white p-8 shadow-sm hover:shadow-md transition-shadow dark:border-gray-700 dark:bg-gray-800">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-red-600">
                                        <svg className="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <h3 className="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                                        Secure by Default
                                    </h3>
                                    <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        Encrypted credentials, SSH key authentication, and isolated containers for each site.
                                    </p>
                                </div>

                                {/* Feature 6 */}
                                <div className="relative rounded-2xl border border-gray-200 bg-white p-8 shadow-sm hover:shadow-md transition-shadow dark:border-gray-700 dark:bg-gray-800">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-600">
                                        <svg className="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <h3 className="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                                        Easy Management
                                    </h3>
                                    <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        Start, stop, update, and delete sites with a single click. Full control at your fingertips.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Stats Section */}
                        <div className="mx-auto mt-24 max-w-7xl">
                            <div className="rounded-2xl bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-16 shadow-xl sm:px-12 lg:px-16">
                                <div className="mx-auto max-w-4xl">
                                    <h2 className="text-3xl font-bold tracking-tight text-white sm:text-4xl text-center">
                                        Everything you need to manage WordPress
                                    </h2>
                                    <p className="mt-4 text-lg text-blue-100 text-center">
                                        Built with modern technologies for performance and reliability
                                    </p>
                                    <dl className="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-3">
                                        <div className="flex flex-col items-center">
                                            <dt className="text-4xl font-bold text-white">Docker</dt>
                                            <dd className="mt-2 text-sm text-blue-100">Container Technology</dd>
                                        </div>
                                        <div className="flex flex-col items-center">
                                            <dt className="text-4xl font-bold text-white">Laravel</dt>
                                            <dd className="mt-2 text-sm text-blue-100">Backend Framework</dd>
                                        </div>
                                        <div className="flex flex-col items-center">
                                            <dt className="text-4xl font-bold text-white">React</dt>
                                            <dd className="mt-2 text-sm text-blue-100">Frontend Framework</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        {/* CTA Section */}
                        <div className="mx-auto mt-24 max-w-4xl text-center">
                            <h2 className="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                                Ready to get started?
                            </h2>
                            <p className="mt-4 text-lg text-gray-600 dark:text-gray-400">
                                Deploy your first WordPress site in minutes
                            </p>
                            <div className="mt-8 flex items-center justify-center gap-x-6">
                                {!auth.user && canRegister && (
                                    <Link
                                        href={register()}
                                        className="rounded-lg bg-blue-600 px-8 py-4 text-lg font-semibold text-white shadow-sm hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition-all"
                                    >
                                        Create Free Account
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>
                </main>

                {/* Footer */}
                <footer className="border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                    <div className="mx-auto max-w-7xl px-6 py-8 lg:px-8">
                        <div className="flex flex-col items-center justify-between gap-4 sm:flex-row">
                            <div className="flex items-center gap-2">
                                <svg className="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                </svg>
                                <span className="font-semibold text-gray-900 dark:text-white">WP Manager</span>
                            </div>
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                © {new Date().getFullYear()} WP Manager. All rights reserved. Powered by alamingemamin
                            </p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}