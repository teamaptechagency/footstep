<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function index(Request $request)
    {
        $sort_by = $request->input('sort', 'newest');
        $search = $request->input('search', '');

        $files = File::where('file_original_name', 'like', '%' . $search . '%')
                    ->orderBy($this->getSortColumn($sort_by), $this->getSortDirection($sort_by))
                    ->paginate(10);

        return view('backend.uploaded_files.index', compact('files', 'sort_by', 'search'));
    }

    private function getSortColumn($sort_by)
    {
        switch ($sort_by) {
            case 'oldest':
                return 'created_at';
            case 'smallest':
            case 'largest':
                return 'file_size';
            case 'newest':
            default:
                return 'created_at';
        }
    }

    private function getSortDirection($sort_by)
    {
        switch ($sort_by) {
            case 'oldest':
            case 'smallest':
                return 'asc';
            case 'largest':
            case 'newest':
            default:
                return 'desc';
        }
    }

    public function create()
    {
        return view('backend.uploaded_files.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // Adjust max file size as necessary
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $size = $file->getSize();

            $storedFileName = $file->store('uploads', 'public');

            File::create([
                'file_original_name' => $originalName,
                'file_name' => $storedFileName,
                'extension' => $extension,
                'file_size' => $size,
                'type' => $this->getType($extension), // Implement getType method as needed
            ]);

            return redirect()->route('files.index')->with('success', 'File uploaded successfully');
        }

        return back()->withInput()->withErrors(['file' => 'File upload failed']);
    }

    private function getType($extension)
    {
        // Add more types as needed
        switch ($extension) {
            case 'pdf':
            case 'doc':
            case 'docx':
                return 'document';
            case 'jpg':
            case 'jpeg':
            case 'png':
                return 'image';
            case 'mp4':
            case 'avi':
                return 'video';
            default:
                return 'other';
        }
    }

    public function destroy($id)
    {
        $file = File::findOrFail($id);

        // Delete file from storage
        Storage::disk('public')->delete($file->file_name);

        // Delete record from database
        $file->delete();

        return redirect()->route('files.index')->with('success', 'File deleted successfully');
    }
}
