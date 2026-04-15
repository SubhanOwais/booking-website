<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class AdminGalleryController extends Controller
{
    /**
     * Display the bus gallery management page
     */
    public function index()
    {
        return Inertia::render('Admin/Gallery/index');
    }

    /**
     * Get all bus galleries (API endpoint)
     */
    public function get_bus_galleries()
    {
        try {
            $galleries = Gallery::orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => true,
                'data' => $galleries,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch galleries',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single bus gallery by ID
     */
    public function get_bus_gallery_detail($id)
    {
        try {
            $gallery = Gallery::find($id);

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
     * Add or update bus gallery
     */
    public function add_bus_gallery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ServiceType' => 'required|string|max:255',
            'ServiceName' => 'required|string|max:255',
            'Paragraph' => 'nullable|string',
            'Type' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
            'Tags' => 'nullable|array',
            'mainImage' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'Images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check if updating existing gallery
            $galleryId = $request->input('id');
            $gallery = $galleryId ? Gallery::find($galleryId) : new Gallery();

            if ($galleryId && !$gallery) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gallery not found',
                ], 404);
            }

            // Update basic fields
            $gallery->ServiceType = $request->ServiceType;
            $gallery->ServiceName = $request->ServiceName;
            $gallery->Paragraph = $request->Paragraph;
            $gallery->Type = $request->Type;
            $gallery->active = $request->active ?? true;

            // Handle tags
            if ($request->has('Tags') && is_array($request->Tags)) {
                $gallery->Tags = implode(',', $request->Tags);
            }

            // Handle main image
            if ($request->hasFile('mainImage')) {
                // Delete old main image if exists
                if ($gallery->MainImage && Storage::disk('public')->exists($gallery->MainImage)) {
                    Storage::disk('public')->delete($gallery->MainImage);
                }

                $mainImage = $request->file('mainImage');
                $mainImageName = time() . '_main_' . $mainImage->getClientOriginalName();
                $mainImagePath = $mainImage->storeAs('Images/Gallery/MainImages/', $mainImageName, 'public');
                $gallery->MainImage = $mainImagePath;
            }

            // Handle gallery images
            if ($request->hasFile('Images')) {
                $existingImages = $gallery->Images ?? '';
                $imagesArray = $existingImages ? explode(', ', $existingImages) : [];

                foreach ($request->file('Images') as $image) {
                    $imageName = time() . '_' . uniqid() . '_' . $image->getClientOriginalName();
                    $imagePath = $image->storeAs('Images/Gallery/GalleryImages/', $imageName, 'public');
                    $imagesArray[] = $imagePath;
                }

                $gallery->Images = implode(', ', array_filter($imagesArray));
            }

            $gallery->save();

            return response()->json([
                'status' => true,
                'message' => $galleryId ? 'Gallery updated successfully' : 'Gallery created successfully',
                'data' => $gallery,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to save gallery',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete main image
     */
    public function delete_main_image($encodedName, $id)
    {
        try {
            $name = base64_decode($encodedName);
            $gallery = Gallery::find($id);

            if (!$gallery) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gallery not found',
                ], 404);
            }

            if ($gallery->MainImage === $name) {
                // Delete the image from storage
                if (Storage::disk('public')->exists($name)) {
                    Storage::disk('public')->delete($name);
                }

                // Remove from database
                $gallery->MainImage = null;
                $gallery->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Main image deleted successfully',
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Image not found',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete gallery image
     */
    public function delete_gallery_image($encodedName, $id)
    {
        try {
            $name = base64_decode($encodedName);
            $gallery = Gallery::find($id);

            if (!$gallery) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gallery not found',
                ], 404);
            }

            $imagesArray = explode(', ', $gallery->Images);
            $key = array_search($name, $imagesArray);

            if ($key !== false) {
                // Remove the image from the array
                unset($imagesArray[$key]);
                $newImagesString = implode(', ', array_values($imagesArray));
                $gallery->Images = $newImagesString;
                $gallery->save();

                // Delete the image from storage
                if (Storage::disk('public')->exists($name)) {
                    Storage::disk('public')->delete($name);
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Gallery image deleted successfully',
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Image not found in gallery',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete entire bus gallery
     */
    public function delete_bus_gallery($id)
    {
        try {
            $gallery = Gallery::find($id);

            if (!$gallery) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gallery not found',
                ], 404);
            }

            // Delete main image
            if ($gallery->MainImage && Storage::disk('public')->exists($gallery->MainImage)) {
                Storage::disk('public')->delete($gallery->MainImage);
            }

            // Delete gallery images
            if ($gallery->Images) {
                $imagesArray = explode(', ', $gallery->Images);
                foreach ($imagesArray as $image) {
                    if (Storage::disk('public')->exists($image)) {
                        Storage::disk('public')->delete($image);
                    }
                }
            }

            $gallery->delete();

            return response()->json([
                'status' => true,
                'message' => 'Gallery deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete gallery',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
