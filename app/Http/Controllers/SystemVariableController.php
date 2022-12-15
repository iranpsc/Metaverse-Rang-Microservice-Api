<?php

namespace App\Http\Controllers;

use App\Http\Resources\SystemVariableResource;
use App\Models\SystemVariable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SystemVariableController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $timings = SystemVariable::whereIn('slug', [
            'show-ads-time',
            'show-correct-answer-time',
            'answer-question-time'
        ])->get();
        return SystemVariableResource::collection($timings);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\SystemVariable $systemVariable
     * @return \Illuminate\Http\Response
     */
    public function show(SystemVariable $systemVariable)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\SystemVariable $systemVariable
     * @return \Illuminate\Http\Response
     */
    public function edit(SystemVariable $systemVariable)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\SystemVariable $systemVariable
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SystemVariable $systemVariable)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\SystemVariable $systemVariable
     * @return \Illuminate\Http\Response
     */
    public function destroy(SystemVariable $systemVariable)
    {
        //
    }
}
