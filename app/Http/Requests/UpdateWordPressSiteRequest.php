<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWordPressSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'domain' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('wordpress_sites', 'domain')->ignore($this->route('site'))
            ],
            'server_ip' => ['sometimes', 'required', 'ip'],
            'server_port' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'server_username' => ['sometimes', 'required', 'string', 'max:255'],
            'server_password' => ['nullable', 'string', 'min:8'],
            'server_ssh_key' => ['nullable', 'string'],
            'wp_version' => ['sometimes', 'string', 'max:50'],
            'container_port' => ['sometimes', 'required', 'integer', 'min:1024', 'max:65535'],
            'ssl_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'domain.unique' => 'This domain is already in use by another site.',
            'server_ip.ip' => 'Please provide a valid IP address.',
            'container_port.min' => 'Container port must be at least 1024.',
        ];
    }
}