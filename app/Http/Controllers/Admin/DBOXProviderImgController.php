<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DBOXProvider;
use App\Models\DBOXProviderImg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DBOXProviderImgController extends Controller
{
    public function store(Request $request, DBOXProvider $provider)
    {
        $validated = $request->validate([
            'image'      => ['nullable', 'image', 'max:5120'],
            'path'       => ['nullable', 'string', 'max:255'],
            'label'      => ['nullable', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (!$request->hasFile('image') && empty($validated['path'])) {
            return back()->withErrors(['image' => 'Upload image OR fill in existing path.']);
        }

        $isPrimary = (bool)($validated['is_primary'] ?? true);
        $sortOrder = (int)($validated['sort_order'] ?? 0);

        $path = $validated['path'] ?? null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            $dir = public_path('images/providers');
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $baseName = Str::slug($provider->code ?: $provider->name ?: 'provider');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $filename = $baseName . '-' . Str::random(10) . '.' . $ext;

            $file->move($dir, $filename);

            $path = 'images/providers/' . $filename;
        } else {
            $path = ltrim($path, '/');
        }

        DB::transaction(function () use ($provider, $path, $validated, $isPrimary, $sortOrder) {
            if ($isPrimary) {
                DBOXProviderImg::where('provider_id', $provider->id)->update(['is_primary' => false]);
            }

            DBOXProviderImg::create([
                'provider_id' => $provider->id,
                'path'        => $path,
                'label'       => $validated['label'] ?? null,
                'is_primary'  => $isPrimary,
                'sort_order'  => $sortOrder,
            ]);
        });

        return back()->with('success', 'Provider logo/image saved.');
    }

    public function destroy(DBOXProviderImg $img)
    {
        $img->delete();
        return back()->with('success', 'Provider image removed.');
    }

    public function setPrimary(DBOXProviderImg $img)
    {
        DB::transaction(function () use ($img) {
            DBOXProviderImg::where('provider_id', $img->provider_id)->update(['is_primary' => false]);
            $img->update(['is_primary' => true]);
        });

        return back()->with('success', 'Primary provider image updated.');
    }
}
