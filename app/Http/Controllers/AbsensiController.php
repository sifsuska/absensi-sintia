<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Absensi;
use App\Models\Division;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


use function PHPUnit\Framework\isNull;

class AbsensiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user_id = auth()->user()->id;
        // dd($user_id);

        // Cari absensi berdasarkan user_id dan tanggal hari ini
        $absensi = Absensi::where('user_id', $user_id)->where('date', date('d/m/Y'))->first();

        if (empty($absensi)) {
            return view('dashboard.absensi.notyet', [
                "title" => "Jangan Lupa Absen",
                'active' => 'dashboard'
            ]);
        }

        $absensiIn = $absensi->in ?? false;
        $absensiOut = $absensi->out ?? false;

        if ($absensiIn) {
            if ($absensi->status) {
                if (is_null($absensi->out)) {
                    return view('dashboard.absensi.working', [
                        'title' => 'Semangat!',
                        'absensi' => $absensi
                    ]);
                }
                return view('dashboard.absensi.complete', [
                    'title' => 'Terimakasih',
                    'absensi' => $absensi
                ]);
            }
            return view('dashboard.absensi.hopeurok', [
                'title' => 'See you next time'
            ]);
        }

        return view('dashboard.absensi.notyet', [
            'title' => 'Absen',
            'absensi' => $absensi
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = User::with(['absensis' => function ($query) {

            $query->where('date', date('d/m/Y'));
        }])->where('id', auth()->user()->id)->first();

        if ($user && $user->absensis->isNotEmpty()) {
            return back()->with('error', 'Kamu sudah absen/presensi hari ini');
        }

        $user = User::find(auth()->user()->id);
        $title = "Absen";
        return view("dashboard.absensi.index", compact("user", 'title'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {


        $user = User::with(['absensis' => function ($query) {

            $query->where('date', date('d/m/Y'));
        }])->where('id', auth()->user()->id)->first();

        if ($user && $user->absensis->isNotEmpty()) {
            return back()->with('error', 'Kamu sudah absen/presensi hari ini');
        }

        try {
            $validatedData = $request->validate([
                'why' => 'required',
                'image' => 'image|file|nullable',
                'reason' => 'nullable'
            ]);

            if ($request->file('image')) {
                $validatedData['image'] = $request->file('image')->store('bukti');
            }
            $validatedData['status'] = false;
            $validatedData['reason'] = $validatedData['why'] . ' : ' . $validatedData['reason'];
            $validatedData['date'] = date('d/m/Y');
            $validatedData['in'] = date('h:i');
            $validatedData['user_id'] = auth()->user()->id;
            $absensi = Absensi::create($validatedData);
        } catch (\Throwable $th) {

            return back()->with('error', $th->getMessage());
            // dd($th);
        }

        return redirect('/dashboard/absensi/')->with('success', 'Terima Kasih Sudah Mengabarkan!');
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $absensi = Absensi::find($id);
        if (!$absensi) return abort(404);

        // if (auth()->user()->divisions_id === $absensi->user->divisions_id) {
            if ($absensi->user->id == auth()->user()->id || auth()->user()->is_admin) {
                return view('dashboard.absensi.show', [
                    "title" => "Dashboard | Absensi",
                    'active' => 'dashboard',
                    'absensi' => $absensi,
                ]);
            }
            return abort(401);
        // }
    }

    /**
     * Show the form for editing the specified resource.
     */

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Absensi $absensi)
    {
        if (!auth()->user()->is_admin) abort(404);

        $rules = [
            'id' => 'required',
            'out' => 'required',
        ];

        $validatedData = $request->validate($rules);
        $update['out'] = $validatedData['out'];
        Absensi::where('id', $absensi->id)->update($update);

        return redirect('/dashboard/absensi')->with('success', 'Terima Kasih Sampai Jumpa lagi!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Absensi $absensi)
    {
        //
    }
}
