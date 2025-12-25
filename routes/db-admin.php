<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Database Management Routes (Protected)
|--------------------------------------------------------------------------
|
| These routes allow running database migrations on environments without
| console access (like Render free tier). Protected by authentication
| and a secret token.
|
*/

Route::middleware(['auth:sanctum'])->prefix('db-admin')->group(function () {

    /**
     * Run pending migrations
     * GET /api/db-admin/migrate?token=YOUR_SECRET_TOKEN
     */
    Route::get('/migrate', function (Request $request) {
        // Verify secret token from environment
        $token = config('app.db_admin_token');
        if (!$token || $request->get('token') !== $token) {
            Log::warning('Unauthorized migration attempt', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            Log::info('Running migrations via API');

            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            Log::info('Migration completed', ['output' => $output]);

            return response()->json([
                'success' => true,
                'message' => 'Migrations executed successfully',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error('Migration failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    });

    /**
     * Run fresh migration with seed (DANGEROUS - drops all tables)
     * GET /api/db-admin/migrate-fresh?token=YOUR_SECRET_TOKEN&confirm=yes
     */
    Route::get('/migrate-fresh', function (Request $request) {
        // Verify secret token from environment
        $token = config('app.db_admin_token');
        if (!$token || $request->get('token') !== $token) {
            Log::warning('Unauthorized fresh migration attempt', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Require explicit confirmation
        if ($request->get('confirm') !== 'yes') {
            return response()->json([
                'error' => 'Fresh migration requires confirm=yes parameter. WARNING: This will DROP ALL DATA!',
            ], 400);
        }

        try {
            Log::warning('Running FRESH migrations via API - ALL DATA WILL BE LOST');

            Artisan::call('migrate:fresh', ['--force' => true, '--seed' => true]);
            $output = Artisan::output();

            Log::info('Fresh migration completed', ['output' => $output]);

            return response()->json([
                'success' => true,
                'message' => 'Fresh migration with seed executed successfully',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error('Fresh migration failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    });

    /**
     * Run seeders only
     * GET /api/db-admin/seed?token=YOUR_SECRET_TOKEN
     */
    Route::get('/seed', function (Request $request) {
        // Verify secret token from environment
        $token = config('app.db_admin_token');
        if (!$token || $request->get('token') !== $token) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            Log::info('Running seeders via API');

            Artisan::call('db:seed', ['--force' => true]);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Seeders executed successfully',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error('Seeding failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    });

    /**
     * Check migration status
     * GET /api/db-admin/status?token=YOUR_SECRET_TOKEN
     */
    Route::get('/status', function (Request $request) {
        // Verify secret token from environment
        $token = config('app.db_admin_token');
        if (!$token || $request->get('token') !== $token) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            Artisan::call('migrate:status');
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    });

    /**
     * Clear all caches
     * GET /api/db-admin/clear-cache?token=YOUR_SECRET_TOKEN
     */
    Route::get('/clear-cache', function (Request $request) {
        // Verify secret token from environment
        $token = config('app.db_admin_token');
        if (!$token || $request->get('token') !== $token) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return response()->json([
                'success' => true,
                'message' => 'All caches cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    });
});
