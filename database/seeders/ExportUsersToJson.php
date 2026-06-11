<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExportUsersToJson extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $payload = null;

        User::with('wallet', 'levels.gem')->each(function ($user) use (&$payload) {
            $payload[] = [
                'id' => $user->id,
                'name' => $user->name,
                'wallet' => $user->wallet ? [
                    'psc' => number_format($user->wallet->psc),
                    'blue' => number_format($user->wallet->blue),
                    'red' => number_format($user->wallet->red),
                    'yellow' => number_format($user->wallet->yellow),
                    'irr' => number_format($user->wallet->irr),
                    'satisfaction' => number_format($user->wallet->satisfaction),
                    'effect' => number_format($user->wallet->effect),

                ]: null,
                'levels' => $user->levels ? $user->levels->map(function ($level) {
                    return [
                        'id' => $level->id,
                        'name' => $level->name,
                        'slug' => $level->slug,
                        'gem' => [
                            'png_file' => $level->gem->png_file,
                            'fbx_file' => $level->gem->fbx_file,
                        ],
                    ];
                }) : null,
            ];
        });

        file_put_contents(public_path('users.json'), json_encode($payload, JSON_PRETTY_PRINT));
    }
}
