<?php
namespace App\Services;
use App\Models\BusinessHour; use App\Models\Setting;
use Carbon\CarbonInterface;
class ScheduleService {
    public function isActive(?CarbonInterface $at=null): bool { $override=Setting::valueOf('playback_override','normal');if($override==='force')return true;if($override==='pause')return false;$at=($at?:now())->timezone(Setting::valueOf('timezone',config('app.timezone'))); $h=BusinessHour::where('weekday',$at->dayOfWeekIso)->first(); if(!$h) return true; if($h->is_closed)return false; $time=$at->format('H:i:s'); return ($h->first_start && $time >= $h->first_start && $time < $h->first_end)||($h->second_start && $time >= $h->second_start && $time < $h->second_end); }
}
