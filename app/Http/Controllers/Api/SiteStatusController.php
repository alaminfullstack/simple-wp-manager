<?php

namespace App\Http\Controllers\Api;

use App\Models\Site;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SiteStatusController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'container_name' => 'required|string',
            'status' => 'required|string|in:running,stopped,deploying,failed',
        ]);
        
        $site = Site::where('container_name', $request->container_name)->first();
        
        if (!$site) {
            return response()->json(['error' => 'Site not found'], 404);
        }
        
        $site->status = $request->status;
        $site->save();
        
        return response()->json(['success' => true]);
    }
}
