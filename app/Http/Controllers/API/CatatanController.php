<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Catatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CatatanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $catatans = $request->user()
            ->catatans()
            ->latest()
            ->get()
            ->map(fn (Catatan $catatan) => $this->format($catatan));

        return response()->json([
            'status' => true,
            'message' => 'Daftar catatan berhasil diambil',
            'data' => $catatans,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:100',
            'isi' => 'required|string',
        ], [
            'judul.required' => 'Judul catatan wajib diisi.',
            'judul.max' => 'Judul catatan maksimal 100 karakter.',
            'isi.required' => 'Isi catatan wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $catatan = $request->user()->catatans()->create($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Catatan berhasil dibuat',
            'data' => $this->format($catatan),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $catatan = $request->user()->catatans()->find($id);

        if (! $catatan) {
            return response()->json([
                'status' => false,
                'message' => 'Catatan tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail catatan berhasil diambil',
            'data' => $this->format($catatan),
        ]);
    }

    private function format(Catatan $catatan): array
    {
        return [
            'id' => $catatan->id,
            'judul' => $catatan->judul,
            'isi' => $catatan->isi,
            'created_at' => $catatan->created_at,
            'updated_at' => $catatan->updated_at,
        ];
    }
}
