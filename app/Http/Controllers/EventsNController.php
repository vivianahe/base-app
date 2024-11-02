<?php

namespace App\Http\Controllers;

use App\Models\EventN;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\StateParticipant;

class EventsNController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sql = EventN::get();
        return response()->json($sql);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Verificar si ya existe un evento con el mismo nombre
            $existingEvent = EventN::where('name', $request->name)->first();
            if ($existingEvent) {
                return response()->json(['error' => 'Ya existe un evento con nombre: ']);
            } else {
                $img = $request->file('logo');
                $fileImg = $img->getClientOriginalExtension();

                $hom = EventN::Create([
                    'name' => $request->name,
                    'capacity' => $request->capacity,
                    'date' => $request->date,
                    'hour' => $request->hour,
                    'price' => $request->price,
                    'type_inscription' => 'Manual',
                    'state' => 'active',
                    'user_id' => Auth::user()->id,
                ]);

                $name_img = date('Y_m_d_H_i_s') . '_event_' . $hom->id . '.' . $fileImg;
                $img->move(public_path('support/logoEvent'), $name_img);

                EventN::where('id', $hom->id)->update([
                    'logo' => $name_img
                ]);
                DB::commit();

                return response()->json(['message' => 'Evento creado exitosamente.'], 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $sql = EventN::where('id', $id)->first();
        return response()->json($sql);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $existingEvent = EventN::where('name', $request->name)
            ->where('id', '!=', $request->id) // Excluye el ID que estás actualizando
            ->exists();
        if ($existingEvent) {
            return response()->json(['error' => 'Ya existe un evento con nombre: ']);
        } else {
            $img = EventN::where('logo', $request->logo)->where('id', $request->id)->exists();
            $dataEvent = EventN::where('id', $request->id)->first();
            if ($img) {
                $name_img = $request->logo;
            } else {
                unlink(public_path('support/logoEvent/') . $dataEvent->logo);
                $img = $request->file('logo');
                $fileImg = $img->getClientOriginalExtension();
                $name_img = date('Y_m_d_H_i_s') . '_event_' . $request->id . '.' . $fileImg;
                $img->move(public_path('support/logoEvent'), $name_img);
            }
            EventN::where('id', $request->id)->update([
                'name' => $request->name,
                'capacity' => $request->capacity,
                'date' => $request->date,
                'hour' => $request->hour,
                'price' => $request->price,
                'logo' => $name_img
            ]);
            return response()->json(['message' => 'Evento creado exitosamente.'], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $even = EventN::find($id);
        $participant = StateParticipant::where('event_id', $id)->get();

        if (count($participant) > 0){
            return response()->json([
                'data' => null,
                'message' => "Exists"
            ]);
        } else {
            if ($even) {
                unlink(public_path('/support/logoEvent/') . $even->logo);
                $even->delete();
                return response()->json([
                    'data' => "ok",
                    'message' => "Evento eliminado exitosamente!"
                ]);
            }
        }

    }
}
