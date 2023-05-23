<?php

use Illuminate\Support\Facades\Route;
use Lupennat\NestedForm\Http\Controllers\FieldSyncController;

Route::patch('/{nestedPrefix}/{resource}/creation-fields', [FieldSyncController::class, 'create']);
Route::patch('/{nestedPrefix}/{resource}/{resourceId}/update-fields', [FieldSyncController::class, 'update']);
