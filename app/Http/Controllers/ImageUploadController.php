<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $path = public_path('tinymce_images'); // Path to public/tinymce_images

            // Ensure the directory exists
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            // Move the file to public/tinymce_images without resizing
            $image->move($path, $filename);

            // Return the correct URL of the uploaded image
            return response()->json(['location' => asset('tinymce_images/' . $filename)]);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }
}

}
