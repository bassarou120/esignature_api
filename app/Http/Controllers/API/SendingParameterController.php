<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Sending_Parameter;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class SendingParameterController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $sp= Sending_Parameter::orderBy('created_at','ASC')->get();
        }catch(QueryException $e){
            return $this->sendError('Error while getting data',[]);
        }

        if(!is_null($sp)){
            return $this->sendResponse($sp, 'Sending parameter retrieved successfully.');
        }
        else{
            return $this->sendError('Null data',[]);
        }

    }


    public function getActivatedParameter()
    {
        try {
            $sp= Sending_Parameter::where('is_activated',1)
                                     ->orderBy('created_at','ASC')
                                     ->get();
        }catch(QueryException $e){
            return $this->sendError('Error while getting data',[]);
        }

        if(!is_null($sp)){
            return $this->sendResponse($sp, 'Sending parameter retrieved successfully.');
        }
        else{
            return $this->sendError('Null data',[]);
        }

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
     * @param  \App\Models\Sending_Parameter  $sending_Parameter
     * @return \Illuminate\Http\Response
     */
    public function show(Sending_Parameter $sending_Parameter)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Sending_Parameter  $sending_Parameter
     * @return \Illuminate\Http\Response
     */
    public function edit(Sending_Parameter $sending_Parameter)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Sending_Parameter  $sending_Parameter
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sending_Parameter $sending_Parameter)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Sending_Parameter  $sending_Parameter
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sending_Parameter $sending_Parameter)
    {
        //
    }
}
