<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\API\BaseController;
use App\Models\Models;
use App\Models\Sending;
use Illuminate\Http\Request;
use App\Http\Resources\Sending as SendingResource;


class ModelsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $model = Sending::where('register_as_model',1)
            ->where('is_registed',1)
            ->orderBy('id','DESC')
            ->get();
        return $this->sendResponse(SendingResource::collection($model), 'Model retrieved successfully.');

    }

    public function getModelByUser($id_user){
        $model = Sending::where('created_by',$id_user)->where('register_as_model',1)->where('is_registed',1)->orderBy('sendings.id','DESC')->get();
        return $this->sendResponse(SendingResource::collection($model), 'Model retrieved successfully.');
    }

    public function getModelByUserAndSignatureType($id_user,$id_type_signature){
        $model = Sending::where('created_by',$id_user)
                          ->where('register_as_model',1)
                          ->where('is_registed',1)
                          ->where('id_type_signature',$id_type_signature)
                          ->orderBy('sendings.id','DESC')
                          ->get();
        return $this->sendResponse(SendingResource::collection($model), 'Model retrieved successfully.');
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Models  $models
     * @return \Illuminate\Http\Response
     */
    public function show(Models $models)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Models  $models
     * @return \Illuminate\Http\Response
     */
    public function edit(Models $models)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Models  $models
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Models $models)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Models  $models
     * @return \Illuminate\Http\Response
     */
    public function destroy(Models $models)
    {
        //
    }
}
