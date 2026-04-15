<?php
// app/Http/Controllers/ProfileController.php

namespace App\Http\Controllers\WebPage;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Gallery;
use App\Models\TicketingSeat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GalleryController extends Controller
{
    public function get_bus_gallery_detail($id)
    {
        try {
            $gallery = Gallery::where('ServiceType', $id)->first();

            // dd($gallery);

            if (!$gallery) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gallery not found',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $gallery,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch gallery',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get galleries by type
     */
    public function get_galleries_by_type(Request $request)
    {
        try {
            $type = $request->query('type');

            $query = Gallery::where('active', true);

            if ($type) {
                $query->where('Type', $type);
            }

            $galleries = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => true,
                'data' => $galleries,
                'count' => $galleries->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch galleries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all galleries with types count
     */
    public function get_all_galleries_with_stats()
    {
        try {
            $galleries = Gallery::where('active', true)
                ->orderBy('created_at', 'desc')
                ->get();

            // Count by type
            $typeCounts = [
                'Bus Gallery' => Gallery::where('active', true)->where('Type', 'Bus Gallery')->count(),
                'Events' => Gallery::where('active', true)->where('Type', 'Events')->count(),
                'Up Coming Buses' => Gallery::where('active', true)->where('Type', 'Up Coming Buses')->count(),
            ];

            return response()->json([
                'status' => true,
                'data' => $galleries,
                'type_counts' => $typeCounts,
                'total' => $galleries->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch galleries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bus gallery items for bus types section
     */
    public function get_bus_gallery_for_types()
    {
        try {
            // Get only active Bus Gallery items
            $galleries = Gallery::where('active', true)
                ->where('Type', 'Bus Gallery')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($gallery) {
                    // Ensure we have proper data structure
                    return [
                        'id' => $gallery->id,
                        'ServiceType' => $gallery->ServiceType,
                        'ServiceName' => $gallery->ServiceName,
                        'MainImage' => $gallery->MainImage,
                        'Images' => $gallery->Images,
                        'Paragraph' => $gallery->Paragraph,
                        'Type' => $gallery->Type,
                        'Tags' => $gallery->Tags,
                        'created_at' => $gallery->created_at,
                        'updated_at' => $gallery->updated_at,
                    ];
                });

            return response()->json([
                'status' => true,
                'data' => $galleries,
                'count' => $galleries->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch bus gallery',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
