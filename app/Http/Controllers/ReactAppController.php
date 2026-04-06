<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReactAppController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $path = trim($request->path(), '/');

        if ($path === 'kinerja-penjualan') {
            abort_unless($user->can('access-performance'), 403);
        }

        if ($path === 'chat-reviews') {
            abort_unless($user->can('access-chat-reviews'), 403);
        }

        if ($path === 'knowledge-queue') {
            abort_unless($user->can('access-knowledge-queue'), 403);
        }

        return view('react.app', [
            'boot' => [
                'user' => [
                    'name' => $user->name,
                    'role' => $user->role,
                    'roleLabel' => $user->roleLabel(),
                    'initials' => strtoupper(substr($user->name, 0, 1)),
                ],
                'csrfToken' => csrf_token(),
                'basePath' => '',
                'routes' => [
                    'logout' => route('logout'),
                    'prospectCreate' => route('prospects.create'),
                    'chatReviewCreate' => route('chat-reviews.create'),
                ],
                'abilities' => [
                    'accessPerformance' => $user->can('access-performance'),
                    'accessChatReviews' => $user->can('access-chat-reviews'),
                    'accessKnowledgeQueue' => $user->can('access-knowledge-queue'),
                    'manageUsers' => $user->can('manage-users'),
                    'manageSystemSettings' => $user->can('manage-system-settings'),
                ],
            ],
        ]);
    }
}
