<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WordPressSite;
use Illuminate\Http\Request;

class StatusApiController extends Controller
{
    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'container_name' => 'required|string',
            'status' => 'required|string|in:running,stopped,paused,not_found,unknown'
        ]);

        try {
            $site = WordPressSite::where('container_name', $validated['container_name'])->first();
            
            if (!$site) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site not found'
                ], 404);
            }

            // Map monitor script statuses to our statuses
            $statusMap = [
                'running' => 'running',
                'stopped' => 'stopped',
                'paused' => 'stopped',
                'not_found' => 'error',
                'unknown' => 'error'
            ];

            $newStatus = $statusMap[$validated['status']] ?? 'error';
            
            $site->update([
                'status' => $newStatus,
                'error_message' => $validated['status'] === 'not_found' ? 'Container not found' : null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'site' => [
                    'id' => $site->id,
                    'name' => $site->site_name,
                    'status' => $site->status
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'updates' => 'required|array',
            'updates.*.container_name' => 'required|string',
            'updates.*.status' => 'required|string|in:running,stopped,paused,not_found,unknown'
        ]);

        $results = [];
        $statusMap = [
            'running' => 'running',
            'stopped' => 'stopped',
            'paused' => 'stopped',
            'not_found' => 'error',
            'unknown' => 'error'
        ];

        foreach ($validated['updates'] as $update) {
            try {
                $site = WordPressSite::where('container_name', $update['container_name'])->first();
                
                if ($site) {
                    $newStatus = $statusMap[$update['status']] ?? 'error';
                    
                    $site->update([
                        'status' => $newStatus,
                        'error_message' => $update['status'] === 'not_found' ? 'Container not found' : null
                    ]);

                    $results[] = [
                        'container_name' => $update['container_name'],
                        'success' => true
                    ];
                } else {
                    $results[] = [
                        'container_name' => $update['container_name'],
                        'success' => false,
                        'message' => 'Site not found'
                    ];
                }
            } catch (\Exception $e) {
                $results[] = [
                    'container_name' => $update['container_name'],
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }
}