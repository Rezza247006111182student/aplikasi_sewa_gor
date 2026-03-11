<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Gelanggang;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GelanggangController extends Controller
{
    // Mengambil daftar gelanggang dengan filter opsional.
    public function index(Request $request): JsonResponse
    {
        $query = Gelanggang::query()->with(['images', 'jadwalOperasional']);

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->string('jenis'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json($query->latest()->get());
    }

    // Mengambil detail satu gelanggang beserta relasinya.
    public function show(int $id): JsonResponse
    {
        $gelanggang = Gelanggang::with(['images', 'jadwalOperasional'])->findOrFail($id);

        return response()->json($gelanggang);
    }

    // Mengambil jadwal operasional dan slot booking pada tanggal tertentu.
    public function jadwal(Request $request, int $id): JsonResponse
    {
        $gelanggang = Gelanggang::with('jadwalOperasional')->findOrFail($id);

        $tanggal = Carbon::parse($request->get('tanggal', now()->toDateString()));
        $hariMap = [
            'Monday' => 'senin',
            'Tuesday' => 'selasa',
            'Wednesday' => 'rabu',
            'Thursday' => 'kamis',
            'Friday' => 'jumat',
            'Saturday' => 'sabtu',
            'Sunday' => 'minggu',
        ];

        $hari = $hariMap[$tanggal->englishDayOfWeek] ?? 'senin';
        $operasional = $gelanggang->jadwalOperasional->firstWhere('hari', $hari);

        $bookings = Booking::query()
            ->where('gelanggang_id', $gelanggang->id)
            ->whereDate('tanggal', $tanggal->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('jam_mulai')
            ->get(['jam_mulai', 'jam_selesai', 'status']);

        return response()->json([
            'tanggal' => $tanggal->toDateString(),
            'hari' => $hari,
            'operasional' => $operasional,
            'booked_slots' => $bookings,
        ]);
    }

    // Menyimpan data gelanggang baru lengkap dengan galeri dan jadwal.
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'jenis' => ['required', 'in:badminton,basket,futsal,tenis,voli'],
            'deskripsi' => ['nullable', 'string'],
            'harga_per_jam' => ['required', 'numeric', 'min:0'],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:aktif,nonaktif,maintenance'],
            'foto_utama' => ['nullable', 'file', 'image', 'max:4096'],
        ]);

        $validated['fasilitas'] = $this->resolveFasilitas($request->input('fasilitas'));
        $validated['foto_utama'] = $this->resolveFotoUtama($request);

        $gelanggang = Gelanggang::create($validated);

        // Simpan galeri gambar ke gelanggang_images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                if ($image->isValid()) {
                    $path = $image->store('gelanggang', 'public');
                    $gelanggang->images()->create([
                        'path' => Storage::url($path),
                        'urutan' => $index + 1,
                    ]);
                }
            }
        }

        // Simpan jadwal operasional
        $this->syncJadwal($gelanggang, $request->input('jadwal'));

        return response()->json($gelanggang->load(['images', 'jadwalOperasional']), 201);
    }

    // Memperbarui data gelanggang, gambar, dan jadwal operasionalnya.
    public function update(Request $request, int $id): JsonResponse
    {
        $gelanggang = Gelanggang::findOrFail($id);

        $validated = $request->validate([
            'nama' => ['sometimes', 'string', 'max:100'],
            'jenis' => ['sometimes', 'in:badminton,basket,futsal,tenis,voli'],
            'deskripsi' => ['nullable', 'string'],
            'harga_per_jam' => ['sometimes', 'numeric', 'min:0'],
            'kapasitas' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:aktif,nonaktif,maintenance'],
            'foto_utama' => ['nullable', 'file', 'image', 'max:4096'],
        ]);

        if ($request->has('fasilitas')) {
            $validated['fasilitas'] = $this->resolveFasilitas($request->input('fasilitas'));
        }

        $newFoto = $this->resolveFotoUtama($request);
        if ($newFoto) {
            $this->deleteStoredImage($gelanggang->foto_utama);
            $validated['foto_utama'] = $newFoto;
        }

        $gelanggang->update($validated);

        // Hapus gambar galeri yang dipilih
        if ($request->has('delete_image_ids')) {
            $deleteIds = $request->input('delete_image_ids');
            if (is_string($deleteIds)) {
                $deleteIds = json_decode($deleteIds, true) ?? [];
            }
            foreach ($gelanggang->images()->whereIn('id', $deleteIds)->get() as $img) {
                $this->deleteStoredImage($img->path);
            }
            $gelanggang->images()->whereIn('id', $deleteIds)->delete();
        }

        // Tambah gambar galeri baru
        if ($request->hasFile('images')) {
            $maxUrutan = $gelanggang->images()->max('urutan') ?? 0;
            foreach ($request->file('images') as $index => $image) {
                if ($image->isValid()) {
                    $path = $image->store('gelanggang', 'public');
                    $gelanggang->images()->create([
                        'path' => Storage::url($path),
                        'urutan' => $maxUrutan + $index + 1,
                    ]);
                }
            }
        }

        // Sinkronisasi jadwal operasional
        if ($request->has('jadwal')) {
            $this->syncJadwal($gelanggang, $request->input('jadwal'));
        }

        return response()->json($gelanggang->load(['images', 'jadwalOperasional']));
    }

    // Menghapus gelanggang beserta file gambar dan data terkait.
    public function destroy(int $id): JsonResponse
    {
        $gelanggang = Gelanggang::with('images')->findOrFail($id);

        // Hapus semua gambar galeri dari storage
        foreach ($gelanggang->images as $image) {
            $this->deleteStoredImage($image->path);
        }
        $gelanggang->images()->delete();
        $gelanggang->jadwalOperasional()->delete();

        $this->deleteStoredImage($gelanggang->foto_utama);
        $gelanggang->delete();

        return response()->json([
            'message' => 'Gelanggang berhasil dihapus.',
        ]);
    }

    // Menyinkronkan data jadwal operasional ke tabel terkait.
    private function syncJadwal(Gelanggang $gelanggang, mixed $jadwal): void
    {
        if (is_string($jadwal)) {
            $jadwal = json_decode($jadwal, true);
        }

        if (!is_array($jadwal) || empty($jadwal)) {
            return;
        }

        $gelanggang->jadwalOperasional()->delete();

        foreach ($jadwal as $row) {
            if (empty($row['hari'])) {
                continue;
            }

            $isLibur = !empty($row['is_libur']);
            $jamBuka = $row['jam_buka'] ?? '07:00';
            $jamTutup = $row['jam_tutup'] ?? '22:00';

            $gelanggang->jadwalOperasional()->create([
                'hari' => $row['hari'],
                'jam_buka' => $isLibur ? '07:00' : $jamBuka,
                'jam_tutup' => $isLibur ? '22:00' : $jamTutup,
                'is_libur' => $isLibur,
            ]);
        }
    }

    // Mengubah input fasilitas menjadi array yang bersih dan konsisten.
    private function resolveFasilitas(mixed $fasilitas): ?array
    {
        if (is_array($fasilitas)) {
            return array_values(array_filter($fasilitas, fn ($item) => is_string($item) && trim($item) !== ''));
        }

        if (!is_string($fasilitas) || trim($fasilitas) === '') {
            return null;
        }

        $decoded = json_decode($fasilitas, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter($decoded, fn ($item) => is_string($item) && trim($item) !== ''));
        }

        return array_values(array_filter(array_map('trim', explode(',', $fasilitas))));
    }

    // Menyimpan foto utama baru atau menerima URL yang dikirim dari request.
    private function resolveFotoUtama(Request $request): ?string
    {
        if ($request->hasFile('foto_utama') && $request->file('foto_utama')->isValid()) {
            $path = $request->file('foto_utama')->store('gelanggang', 'public');
            return Storage::url($path);
        }

        $url = $request->input('foto_utama');
        if (is_string($url) && trim($url) !== '') {
            return $url;
        }

        return null;
    }

    // Menghapus file gambar dari storage jika berasal dari disk publik.
    private function deleteStoredImage(?string $url): void
    {
        if (!$url || !str_starts_with($url, '/storage/')) {
            return;
        }

        $path = str_replace('/storage/', '', $url);
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
