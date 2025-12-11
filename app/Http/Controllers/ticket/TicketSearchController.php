<?php

/*
 * Function TicketSearchController.php:
 *    - Search tickets based on various criteria.
 *    - Support filtering by status, category, date range, and keywords.
 *    - Provide results for Pengadu and Pegawai Tugasan.
 *    - Search users by name or email for "Bagi Pihak" ticket creation.
 */

namespace App\Http\Controllers\Ticket;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TicketSearchController extends Controller
{
    public function searchUserByName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2'
        ]);

        $name = $request->input('name');

        // Search for user by name with relationships
        $user = User::with(['sektor', 'bahagian', 'seksyen', 'unit', 'jawatan', 'skim'])
            ->where('nama', 'like', '%' . $name . '%')
            ->where('id', '!=', Auth::id()) // Exclude current user
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemui dengan nama tersebut.'
            ], 404);
        }

        // Prepare user data with related information
        $userData = [
            'id' => $user->id,
            'nama' => $user->nama,
            'name' => $user->nama, // fallback
            'email' => $user->email,
            'kod_sektor' => $user->kod_sektor,
            'kod_bah' => $user->kod_bah,
            'kod_sec' => $user->kod_sec,
            'kod_unit' => $user->kod_unit,
            'kod_skim' => $user->kod_skim,
            'nama_gred' => $user->nama_gred,
            'tel_no_p' => $user->tel_no_p,
            'tel_no_hp' => $user->tel_no_hp,
        ];

        // Get related names if relationships exist
        if ($user->sektor) {
            $userData['nama_sektor'] = $user->sektor->nama_sektor;
        }

        if ($user->bahagian) {
            $userData['nama_bah'] = $user->bahagian->nama_bah;
        }

        if ($user->seksyen) {
            $userData['nama_sec'] = $user->seksyen->nama_sec;
        }

        if ($user->unit) {
            $userData['nama_unit'] = $user->unit->nama_unit;
        }

        if ($user->jawatan) {
            $userData['nama_jawatan'] = $user->jawatan->nama_jawatan;
        }

        if ($user->skim) {
            $userData['nama_skim'] = $user->skim->nama_skim;
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berjaya ditemui.',
            'user' => $userData
        ]);
    }

    public function searchUserByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->input('email');

        // Search for user by exact email with relationships
        $user = User::with(['sektor', 'bahagian', 'seksyen', 'unit', 'jawatan', 'skim'])
            ->where('email', $email)
            ->where('id', '!=', Auth::id()) // Exclude current user
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemui dengan email tersebut.'
            ], 404);
        }

        // Prepare user data with related information
        $userData = [
            'id' => $user->id,
            'nama' => $user->nama,
            'name' => $user->nama, // fallback
            'email' => $user->email,
            'kod_sektor' => $user->kod_sektor,
            'kod_bah' => $user->kod_bah,
            'kod_sec' => $user->kod_sec,
            'kod_unit' => $user->kod_unit,
            'kod_skim' => $user->kod_skim,
            'nama_gred' => $user->nama_gred,
            'tel_no_p' => $user->tel_no_p,
            'tel_no_hp' => $user->tel_no_hp,
        ];

        // Get related names if relationships exist
        if ($user->sektor) {
            $userData['nama_sektor'] = $user->sektor->nama_sektor;
        }

        if ($user->bahagian) {
            $userData['nama_bah'] = $user->bahagian->nama_bah;
        }

        if ($user->seksyen) {
            $userData['nama_sec'] = $user->seksyen->nama_sec;
        }

        if ($user->unit) {
            $userData['nama_unit'] = $user->unit->nama_unit;
        }

        if ($user->jawatan) {
            $userData['nama_jawatan'] = $user->jawatan->nama_jawatan;
        }

        if ($user->skim) {
            $userData['nama_skim'] = $user->skim->nama_skim;
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berjaya ditemui.',
            'user' => $userData
        ]);
    }

    public function searchUserAutocomplete(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2'
        ]);

        $query = $request->input('query');

        // Search for users primarily by name (with some email support for edge cases)
        $users = User::select('id', 'nama', 'email')
            ->where(function ($q) use ($query) {
                // Primary search: by name (case insensitive)
                $q->where('nama', 'like', '%' . $query . '%');
                // Secondary search: by email username part (before @)
                $q->orWhere('email', 'like', $query . '%@%');
            })
            ->where('id', '!=', Auth::id()) // Exclude current user
            ->whereNotNull('nama') // Ensure nama is not null
            ->where('nama', '!=', '') // Ensure nama is not empty
            ->where('nama', 'not like', '%@%') // Exclude email-like names
            ->orderBy('nama', 'asc') // Order by name alphabetically
            ->limit(8) // Limit results to prevent too many options
            ->get()
            ->filter(function ($user) {
                // Additional filtering: exclude names that are just initials or codes
                $name = trim($user->nama);

                // Skip if name is just 1-2 characters (likely initials)
                if (strlen($name) <= 2) {
                    return false;
                }

                // Skip if name contains only uppercase letters and spaces (likely codes)
                if (preg_match('/^[A-Z\s]+$/', $name) && strlen($name) <= 5) {
                    return false;
                }

                return true;
            })
            ->values() // Reset array keys
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'nama' => $user->nama,
                    'display' => $user->nama // Only show name in display
                ];
            });

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }
}
