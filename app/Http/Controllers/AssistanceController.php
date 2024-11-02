<?php

namespace App\Http\Controllers;

use App\Models\EventN;
use App\Models\Participant;
use App\Models\EventParticipant;
use App\Models\StateParticipant;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AssistanceController extends Controller
{
    public function getParticipantInscrit(string $id)
    {
        $sql = Participant::join('event_participants', 'event_participants.participant_id', 'participants.id')
            ->join('events_noved', 'events_noved.id', 'event_participants.event_id')
            ->join('state_participants', function ($join) {
                $join->on('state_participants.participant_id', '=', 'participants.id')
                    ->on('state_participants.event_id', '=', 'events_noved.id')
                    ->whereRaw('state_participants.created_at = (select max(created_at) from state_participants where participant_id = participants.id and event_id = events_noved.id)');
            })
            ->select('participants.*', 'events_noved.name as event', 'events_noved.id as event_id', 'state_participants.state', 'event_participants.qr')
            ->where('events_noved.id', $id)
            ->where(function ($query) {
                $query->where('state_participants.state', '=', 'Asistente')
                    ->orWhere('state_participants.state', '=', 'Inscrito');
            })
            ->get();

        return response()->json($sql);
    }
    public function getEventDate()
    {
        $date = date('Y-m-d');

        $sql = EventN::leftJoin('event_participants', 'event_participants.event_id', '=', 'events_noved.id')
            ->leftJoin('participants', 'participants.id', '=', 'event_participants.participant_id')
            ->leftJoin('state_participants', function ($join) use ($date) {
                $join->on('state_participants.participant_id', '=', 'participants.id')
                    ->on('state_participants.event_id', '=', 'events_noved.id')
                    ->whereRaw('state_participants.created_at = (select max(created_at) from state_participants where participant_id = participants.id and event_id = events_noved.id)');
            })
            ->where(function ($query) use ($date) {
                $query->where('state_participants.state', '=', 'Asistente')
                    ->orWhereNull('state_participants.state');
            })
            ->whereDate('events_noved.date', '=', $date)
            ->selectRaw('events_noved.*, COUNT(participants.id) as participant_count')
            ->groupBy('events_noved.id', 'events_noved.name', 'events_noved.capacity', 'events_noved.date', 'events_noved.hour', 'events_noved.price', 'events_noved.type_inscription', 'events_noved.state', 'events_noved.logo', 'events_noved.user_id', 'events_noved.created_at', 'events_noved.updated_at')
            ->get();


        return response()->json($sql);
    }

    public function qrParticipant($email, $event_id)
    {
        $participant = Participant::where('email', $email)->first();
        $state = StateParticipant::where([['event_id', $event_id], ['participant_id', $participant->id]])->orderBy('created_at', 'desc')->first();
        $qrExists = EventParticipant::where([['event_id', $event_id], ['participant_id', $participant->id]])->first();

        if ($state->state === 'Inscrito' && $qrExists->qr !== null) {
            $rutaImagen = public_path('support/qrParticipant/' . $qrExists->qr);
            return response()->file($rutaImagen);
        } else {
            $fileName = 'qr_' . $event_id . '_' . $participant->id . '.png';
            $data = [
                'event_id' => $event_id,
                'participant_id' => $participant->id,
            ];
            $data = base64_encode(json_encode($data));
            QrCode::format('png')->size(300)->margin(2)->generate($data, '../public/support/qrParticipant/' . $fileName);
            EventParticipant::updateOrCreate(
                [
                    'event_id' => $event_id,
                    'participant_id' => $participant->id
                ],
                [
                    'qr' => $fileName,
                ]
            );
            $rutaImagen = public_path('support/qrParticipant/' . $fileName);
            return response()->file($rutaImagen);
        }
    }
}
