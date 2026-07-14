<?php
namespace App\Http\Controllers; use App\Models\Backup; class BackupController extends Controller {public function download(Backup $backup){abort_unless($backup->status==='completed',404);return response()->download('/data/backups/'.$backup->path,$backup->filename);}}
