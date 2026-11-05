<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Program\StoreProgramRequest;
use App\Http\Resources\TrainingProgramResource;
use App\Models\Member;
use App\Models\TrainingProgram;
use App\Services\TrainingProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TrainingProgramController extends Controller
{
    public function __construct(private readonly TrainingProgramService $programs) {}

    /** GET /api/programs?status=proposed&kind= - file de traitement (coach, gérant) */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TrainingProgram::query()->with(['member', 'author', 'reviewer'])->orderByDesc('created_at');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('kind')) {
            $query->ofKind($request->kind);
        }

        return TrainingProgramResource::collection($query->paginate(30)->withQueryString());
    }

    /** GET /api/members/{member}/programs?kind= */
    public function byMember(Request $request, Member $member): JsonResponse
    {
        $query = $member->trainingPrograms()->with(['author', 'reviewer']);
        if ($request->filled('kind')) {
            $query->ofKind($request->kind);
        }

        return TrainingProgramResource::collection($query->get())
            ->additional(['meta' => ['adherence' => $this->programs->adherence($member)]])
            ->response();
    }

    /** POST /api/members/{member}/programs [coach, manager] */
    public function store(StoreProgramRequest $request, Member $member): JsonResponse
    {
        $program = $this->programs->create($member, $request->validated(), $request->user(), $request->boolean('impose'));

        return (new TrainingProgramResource($program))->response()->setStatusCode(201);
    }

    public function show(TrainingProgram $program): TrainingProgramResource
    {
        return new TrainingProgramResource($program->load(['member', 'author', 'reviewer']));
    }

    public function update(StoreProgramRequest $request, TrainingProgram $program): TrainingProgramResource
    {
        return new TrainingProgramResource($this->programs->update($program, $request->validated(), $request->user()));
    }

    public function approve(Request $request, TrainingProgram $program): TrainingProgramResource
    {
        $request->validate(['note' => ['nullable', 'string', 'max:255']]);

        return new TrainingProgramResource($this->programs->approve($program, $request->user(), $request->input('note')));
    }

    public function decline(Request $request, TrainingProgram $program): TrainingProgramResource
    {
        $request->validate(['note' => ['required', 'string', 'min:3', 'max:255']]);

        return new TrainingProgramResource($this->programs->decline($program, $request->user(), $request->note));
    }

    public function archive(Request $request, TrainingProgram $program): TrainingProgramResource
    {
        return new TrainingProgramResource($this->programs->archive($program, $request->user()));
    }
}
