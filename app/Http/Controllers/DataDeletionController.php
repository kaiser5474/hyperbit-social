<?php

namespace App\Http\Controllers;

use App\Models\DataDeletionRequest;
use App\Models\User;
use App\Services\Meta\MetaGraphClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DataDeletionController extends Controller
{
    public function __construct(
        protected MetaGraphClient $client
    ) {}

    public function callback(Request $request): JsonResponse
    {
        $signedRequest = $request->input('signed_request');

        if (! $signedRequest) {
            return response()->json(['error' => 'Missing signed_request'], 400);
        }

        $data = $this->client->parseSignedRequest($signedRequest);

        if (! $data || ! isset($data['user_id'])) {
            Log::warning('Invalid data deletion signed_request');

            return response()->json(['error' => 'Invalid signed_request'], 400);
        }

        $facebookUserId = $data['user_id'];

        Log::info('Data deletion request received', ['facebook_user_id' => $facebookUserId]);

        $deletionRequest = DataDeletionRequest::createForUser($facebookUserId);

        $this->processDataDeletion($facebookUserId, $deletionRequest);

        return response()->json([
            'url' => $deletionRequest->getStatusUrl(),
            'confirmation_code' => $deletionRequest->confirmation_code,
        ]);
    }

    public function status(string $code): View
    {
        $deletionRequest = DataDeletionRequest::where('confirmation_code', $code)->first();

        if (! $deletionRequest) {
            abort(404);
        }

        return view('landing.data-deletion-status', compact('deletionRequest'));
    }

    protected function processDataDeletion(string $facebookUserId, DataDeletionRequest $deletionRequest): void
    {
        $deletionRequest->markAsProcessing();

        $user = User::where('facebook_id', $facebookUserId)->first();
        $deletedData = [];

        if ($user) {
            $postsCount = $user->posts()->count();
            $user->posts()->delete();
            $deletedData['posts_deleted'] = $postsCount;

            $accountsCount = $user->socialAccounts()->count();
            $user->socialAccounts()->delete();
            $deletedData['social_accounts_deleted'] = $accountsCount;

            $deletedData['user_email'] = $user->email;
            $user->update([
                'facebook_id' => null,
                'facebook_token' => null,
                'facebook_token_expires_at' => null,
                'avatar' => null,
            ]);

            $deletedData['user_facebook_data_cleared'] = true;

            Log::info('User data deleted for Facebook user', [
                'facebook_user_id' => $facebookUserId,
                'user_id' => $user->id,
                'deleted_data' => $deletedData,
            ]);
        } else {
            $deletedData['user_found'] = false;

            Log::info('No user found for Facebook deletion request', [
                'facebook_user_id' => $facebookUserId,
            ]);
        }

        $deletionRequest->markAsCompleted($deletedData);
    }
}
