<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWordPressSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:wordpress_sites,domain'],
            'server_ip' => ['required', 'ip'],
            'server_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'server_username' => ['required', 'string', 'max:255'],
            'server_password' => ['nullable', 'string', 'min:8'],
            'server_ssh_key' => ['nullable', 'string'],
            'wp_version' => ['nullable', 'string', 'max:50'],
            'container_port' => ['required', 'integer', 'min:1024', 'max:65535'],
            'db_name' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_]+$/'],
            'db_user' => ['required', 'string', 'max:32', 'regex:/^[a-zA-Z0-9_]+$/'],
            'db_password' => ['required', 'string', 'min:8'],
            'db_host' => ['nullable', 'string', 'max:255'],
            'ssl_enabled' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'domain.unique' => 'This domain is already in use by another site.',
            'server_ip.ip' => 'Please provide a valid IP address.',
            'container_port.min' => 'Container port must be at least 1024.',
            'db_name.regex' => 'Database name can only contain letters, numbers, and underscores.',
            'db_user.regex' => 'Database user can only contain letters, numbers, and underscores.',
            'db_password.min' => 'Database password must be at least 8 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Set defaults
        $this->merge([
            'wp_version' => $this->wp_version ?? 'latest',
            'db_host' => $this->db_host ?? 'mysql',
            'ssl_enabled' => $this->ssl_enabled ?? false,
            'server_port' => $this->server_port ?? 22,
        ]);
    }
}