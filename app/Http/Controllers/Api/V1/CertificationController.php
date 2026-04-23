<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCertificationRequest;
use App\Http\Requests\UpdateCertificationRequest;
use App\Http\Resources\CertificationResource;
use App\Models\Certification;
use App\Services\CloudinaryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CertificationController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinary) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio) {
            return CertificationResource::collection(collect());
        }

        if ($request->boolean('include_inactive')) {
            $certifications = Certification::where('portfolio_id', $portfolio->id)
                ->where('is_active', false)
                ->orderByDesc('issue_date')
                ->get();
        } else {
            $certifications = $portfolio->certifications;
        }

        return CertificationResource::collection($certifications);
    }

    public function store(StoreCertificationRequest $request): CertificationResource|JsonResponse
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio) {
            return response()->json(['message' => 'Portfolio not found. Create your portfolio first.'], 404);
        }

        $validated = $request->validated();

        $certification = Certification::create([
            'portfolio_id'    => $portfolio->id,
            'name'            => $validated['name'],
            'description'     => $validated['description'] ?? null,
            'issuing_entity'  => $validated['issuing_entity'],
            'issue_date'      => $this->parseDate($validated['issue_date']),
            'expiration_date' => isset($validated['expiration_date'])
                ? $this->parseDate($validated['expiration_date'])
                : null,
        ]);

        return (new CertificationResource($certification))->response()->setStatusCode(201);
    }

    public function update(UpdateCertificationRequest $request, Certification $certification): CertificationResource|JsonResponse
    {
        if ($certification->portfolio_id !== $request->user()->portfolio?->id) {
            abort(403);
        }

        if (! $certification->is_active) {
            return response()->json(['message' => 'Cannot update a deactivated certification.'], 403);
        }

        $validated = $request->validated();
        $data      = [];

        if (isset($validated['name'])) {
            $data['name'] = $validated['name'];
        }
        if (array_key_exists('description', $validated)) {
            $data['description'] = $validated['description'];
        }
        if (isset($validated['issuing_entity'])) {
            $data['issuing_entity'] = $validated['issuing_entity'];
        }
        if (isset($validated['issue_date'])) {
            $data['issue_date'] = $this->parseDate($validated['issue_date']);
        }
        if (array_key_exists('expiration_date', $validated)) {
            $data['expiration_date'] = $validated['expiration_date']
                ? $this->parseDate($validated['expiration_date'])
                : null;
        }

        $certification->update($data);

        return new CertificationResource($certification->fresh());
    }

    public function updateImage(Request $request, Certification $certification): CertificationResource|JsonResponse
    {
        if ($certification->portfolio_id !== $request->user()->portfolio?->id) {
            abort(403);
        }

        if (! $certification->is_active) {
            return response()->json(['message' => 'Cannot update a deactivated certification.'], 403);
        }

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->cloudinary->deleteCertificationImage($certification->cloudinary_public_id);

        $uploaded = $this->cloudinary->uploadCertificationImage($request->file('image'));

        $certification->update([
            'image_url'            => $uploaded['url'],
            'cloudinary_public_id' => $uploaded['public_id'],
        ]);

        return new CertificationResource($certification->fresh());
    }

    public function toggle(Request $request, Certification $certification): JsonResponse
    {
        if ($certification->portfolio_id !== $request->user()->portfolio?->id) {
            abort(403);
        }

        $certification->update(['is_active' => ! $certification->is_active]);

        $message = $certification->is_active
            ? 'Certification activated successfully.'
            : 'Certification deactivated successfully.';

        return response()->json([
            'message' => $message,
            'data'    => new CertificationResource($certification->fresh()),
        ]);
    }

    private function parseDate(string $date): string
    {
        return Carbon::createFromFormat('m/Y', $date)->startOfMonth()->toDateString();
    }
}
