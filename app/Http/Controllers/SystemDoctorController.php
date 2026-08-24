<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityEvent;
use App\Services\SystemDoctorService;

class SystemDoctorController extends Controller
{
    public function index(SystemDoctorService $doctor)
    {
        return view('system-doctor', ['result' => $doctor->run(false)]);
    }

    public function repair(SystemDoctorService $doctor)
    {
        $result = $doctor->run(true);
        AdminActivityEvent::create([
            'user_id' => auth()->id(),
            'action' => 'system.doctor',
            'subject_type' => 'system',
            'result' => $result['ok'] ? 'completed' : 'error',
            'details_json' => $result['summary'],
            'error_message' => $result['ok'] ? null : 'La reparación terminó con comprobaciones fallidas.',
        ]);

        return view('system-doctor', [
            'result' => $result,
            'status' => $result['ok'] ? 'Comprobación y reparación terminadas.' : 'La rutina terminó con errores que requieren intervención por SSH.',
        ]);
    }
}
