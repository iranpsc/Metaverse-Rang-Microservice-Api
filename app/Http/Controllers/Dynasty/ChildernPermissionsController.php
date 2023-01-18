<?php

namespace App\Http\Controllers\Dynasty;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateChildrenPermissionsRequest;
class ChildernPermissionsController extends Controller
{
    public function __invoke(UpdateChildrenPermissionsRequest $request, User $user)
    {
        $this->authorize('controlPermissions', $user);
        $user->permissions->update([$request->permission => $request->status]);
        return response()->noContent();
    }
}
