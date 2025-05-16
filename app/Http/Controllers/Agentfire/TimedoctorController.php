<?php

namespace App\Http\Controllers\Agentfire;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TimedoctorService;
use App\Models\Timedoctor;

class TimedoctorController extends Controller
{

    protected $timedoctorService;

    public function __construct(TimedoctorService $timedoctorService)
    {
        $this->timedoctorService = $timedoctorService;
    }

    /* Timedoctor Setting */
    public function handleTimedoctor(Request $request)
    {
        if ($request->isMethod('post')) {
            $formData = [
                'timedoctor_company_id'    => $request->input('timedoctor_company_id'),
                'google_sheet_id'          => $request->input('google_sheet_id'),
                'google_sheet_range'       => 0
            ];

            if ($request->hasFile('google_service_credentials')) {
                $file = $request->file('google_service_credentials');
                if (!$file->isValid() || $file->getClientOriginalExtension() !== 'json') {
                    return back()->with('error', 'Invalid JSON file.');
                }
        
                $jsonContents = file_get_contents($file->getRealPath());
        
                $decoded = json_decode($jsonContents, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return back()->with('error', 'Invalid JSON structure.');
                }
                $formData['google_service_credentials'] = $jsonContents;
            }

            $this->timedoctorService->saveTimedoctorData($formData);
    
            return back()->with('success', 'Settings Updated.');
        }

        $getTimedoctor =  $this->timedoctorService->getTimeDoctor();
    
        return view('timedoctor.index', compact('getTimedoctor'));
    }
    /* Timedoctor Setting */

}
