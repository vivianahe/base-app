<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\EventN;
use App\Models\Participant;
use Codedge\Fpdf\Fpdf\Fpdf;
use Illuminate\Http\Request;
use App\Models\EventParticipant;
use App\Models\StateParticipant;
use App\Models\EmailMod;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use PHPMailer\PHPMailer\PHPMailer;

class ParticipantsController extends Controller
{
    protected $fpdf;

    public function __construct()
    {
        $this->fpdf = new Fpdf;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        switch ($_GET['Q']) {
            case 0:
                $sql = Participant::join('event_participants', 'event_participants.participant_id', 'participants.id')
                    ->join('events_noved', 'events_noved.id', 'event_participants.event_id')
                    ->join('state_participants', function ($join) {
                        $join->on('state_participants.participant_id', '=', 'participants.id')
                            ->on('state_participants.event_id', '=', 'events_noved.id')
                            ->whereRaw('state_participants.created_at = (select max(created_at) from state_participants where participant_id = participants.id and event_id = events_noved.id)');
                    })
                    ->select('participants.*', 'events_noved.name as event', 'events_noved.id as event_id', 'state_participants.state', 'event_participants.qr')
                    ->where('events_noved.id', $_GET['id'])
                    ->get();
                break;
            case 1:
                $sql = Participant::all();
                break;
            case 2:
                $data = explode(" ", $_GET['param']);
                $json = [];
                for ($i = 0; $i < count($data); $i++) {
                    if (is_numeric($data[$i])) {
                        $conditional = ['dni', 'like', '%' . $data[$i] . '%'];
                        array_push($json, $conditional);
                    } else {
                        $conditional = ['name', 'like', '%' . $data[$i] . '%'];
                        array_push($json, $conditional);
                    }
                }
                $sql = Participant::where($json)->limit(5)->get();
                break;
            case 3:
                $sql = StateParticipant::leftJoin('users', 'users.id', 'state_participants.user_id')
                    ->select('state_participants.*', 'users.name')
                    ->where([['event_id', $_GET['event_id']], ['participant_id', $_GET['participant_id']]])
                    ->orderBy('state_participants.created_at', 'desc')
                    ->get();

                break;
        }

        return response()->json($sql);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $existingPart = Participant::where('dni', $request->participant['dni'])
                ->where('id', '!=', $request->participant['id']) // Excluye el ID que estás actualizando
                ->exists();
            if ($existingPart) {
                return response()->json(['error' => 'Ya existe un participante con dni: ']);
            } else {
                if ($request->title === 'AGREGAR PARTICIPANTE') {
                    $participant = Participant::updateOrCreate(
                        [
                            'dni' => $request->participant['dni'],
                            'id' => $request->participant['id']
                        ],
                        [
                            'name' => $request->participant['name'],
                            'lastname' => $request->participant['lastname'],
                            'email' => $request->participant['email'],
                            'country' => $request->participant['country'],
                            'province' => $request->participant['province'],
                            'visitor_typology' => $request->participant['visitor_typology'],
                            'phone' => $request->participant['phone'],
                            'user_id' => Auth::user()->id
                        ]
                    );
                } else {
                    Participant::where('id', $request->participant['id'])->update([
                        'dni' => $request->participant['dni'],
                        'name' => $request->participant['name'],
                        'lastname' => $request->participant['lastname'],
                        'email' => $request->participant['email'],
                        'country' => $request->participant['country'],
                        'province' => $request->participant['province'],
                        'visitor_typology' => $request->participant['visitor_typology'],
                        'phone' => $request->participant['phone'],
                    ]);
                    $participant = Participant::where('id', $request->participant['id'])->first();
                }
            }
            Billing::updateOrCreate(
                [
                    'participant_id' => $participant->id
                ],
                [
                    'company_name' => $request->participant['company_name'],
                    'cif_nif' => $request->participant['cif_nif'],
                    'billing_email' => $request->participant['billing_email'],
                    'billing_address' => $request->participant['billing_address'],
                    'billing_cp' => $request->participant['billing_cp'],
                    'billing_locality' => $request->participant['billing_locality'],
                    'billing_province' => $request->participant['billing_province'],
                    'billing_country' => $request->participant['billing_country'],
                    'participant_id' => $participant->id,
                    'user_id' => Auth::user()->id
                ]
            );
            $state = StateParticipant::where([
                ['event_id', $request->participant['eventP']],
                ['participant_id', $participant->id]
            ])->orderBy('created_at', 'desc')->first();
            $statePre = false;
            if ($state === null || $state->state !== $request->participant['state']) {
                StateParticipant::Create([
                    'event_id' => $request->participant['eventP'],
                    'participant_id' => $participant->id,
                    'user_id' => Auth::user()->id,
                    'state' => $request->participant['state']
                ]);
                if ($request->participant['state'] === 'Preinscrito') {
                    $statePre = true;
                }
            }
            $inscrito = StateParticipant::where([['event_id', $request->participant['eventP']], ['participant_id', $participant->id]])->orderBy('created_at', 'desc')->first();

            if ($inscrito->state === 'Inscrito') {
                $fileName = 'qr_' . $request->participant['eventP'] . '_' . $participant->id . '.png';
                $data = [
                    'event_id' => $request->participant['eventP'],
                    'participant_id' => $participant->id,
                ];
                $data = base64_encode(json_encode($data));

                QrCode::format('png')->size(300)->margin(2)->generate($data, '../public/support/qrParticipant/' . $fileName);
            } else {
                $qr_exist = EventParticipant::where([['event_id', $request->participant['eventP']], ['participant_id', $participant->id]])->first();
                if ($qr_exist) {
                    $fileName = $qr_exist->qr;
                } else {
                    $fileName = null;
                }
            }
            $evenExis = EventParticipant::where([['event_id', $request->participant['eventP']],['participant_id', $participant->id]])->exists();

            EventParticipant::updateOrCreate(
                [
                    'event_id' => $request->participant['eventP'],
                    'participant_id' => $participant->id
                ],
                [
                    'event_id' => $request->participant['eventP'],
                    'participant_id' => $participant->id,
                    'qr' => $fileName
                ]
            );
            if ($inscrito->state === 'Inscrito'){
                $this->sendQrEmailParticipant($request->participant['eventP'], $participant->id);
            }
            if ($statePre && !$evenExis) {
                $sql = EmailMod::first();
                if ($sql) {
                    $eventData = EventN::where('id', $request->participant['eventP'])->first();
                    $data = [
                        "userMail" => $participant->email,
                        "name" => $participant->name,
                        "date" => $eventData->date,
                        "hour" => $eventData->hour,
                        "nameEvent" => $eventData->name,
                        "logo" => $eventData->logo,
                        "year" => date("Y")
                    ];
                    $mail = new PHPMailer(true);
                    $mail->CharSet = 'UTF-8';
                    $asunto = 'Confirmación de Inscripción: ¡Bienvenido/a!';
                    $body = '<!DOCTYPE html>
                    <html lang="es">
                    <head>
                        <meta charset="UTF-8">
                    </head>
                    <body style="font-family: Arial">
                        <table width="100%">
                            <tbody style="color: #3d4852; height: auto; line-height: 1.4; margin: 0px; width: 799px; overflow: visible;">
                                <tr>
                                    <td class="header" style="padding: 25px 0px; text-align: center; background: #f8fafc"><img src="' . url('') . '/support/logoEvent/' .  $data['logo'] . '" alt="Logo Evento" width="10%" max-width="100%"></td>
                                </tr>
                                <tr>
                                    <td class="body" style="padding: 35px;" style="background-color: rgb(255, 255, 255);">
                                        <table class="inner-body" style="background-color: rgb(255, 255, 255); margin: 0px auto; padding: 0px; width: 700px;" align="center">
                                            <tbody>
                                                <tr></tr>
                                                <td class="content-cell">
                                                    <b>
                                                        <h3>Estimado/a ' . $data['name'] . ',</h3>
                                                    </b>
                                                    <p>Hemos recibido correctamente su solicitud para asistir al evento: <b>' . $data['nameEvent'] . '</b> </p>
                                                    <p>En breve confirmaremos su asistencia y le haremos llegar su invitación. </p>
                                                    <p><b>Muchas gracias</b></p>
                                                </td>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="header" style="padding: 25px 0px; text-align: center; background: #f8fafc"></td>
                                </tr>
                            </tbody>
                        </table>
                    </body>
                    </html>';
                    $ss = $sql->SMTPAuth === 1 ? true : false;
                    $mail->CharSet = 'UTF-8';
                    $mail->IsSMTP();
                    $mail->SMTPAuth = $ss; //true
                    $mail->SMTPSecure = $sql->SMTPSecure; //tls
                    $mail->Host = $sql->host; //smtp.gmail.com
                    $mail->Port       = $sql->port; //587
                    $mail->Username = $sql->email; //noreply@aunnait.es
                    $mail->Password = $sql->password; //wzuogblenxhzrkeo
                    $mail->setFrom($sql->email, $sql->from);
                    $mail->addAddress($data['userMail']);
                    $mail->isHTML(true);
                    $mail->Subject = $asunto;
                    $mail->Body = $body;
                    if (!$mail->Send()) {
                        $resultsend = $mail->ErrorInfo;
                    }
                } else {
                    return response()->json([
                        'message' => 'Debes configurar el correo para poder enviar el certificado.', 'success' => false
                    ], 422);
                }
            }
            DB::commit();
            return response()->json(['message' => 'Participante guardado exitosamente.'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::info("error",["Error: "=>$e]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id, $event_id)
    {
        $participants = Participant::where('id', $id)->first();
        $state = StateParticipant::where([
            ['event_id', '=', $event_id],
            ['participant_id', '=', $id]
        ])->orderBy('created_at', 'desc')->first();
        $event_part = EventParticipant::where([
            ['event_id', '=', $event_id],
            ['participant_id', '=', $id]
        ])->first();
        $billing = Billing::where('participant_id', '=', $id)->first();
        $response = [
            'participants' => $participants,
            'state' => $state,
            'event_part' => $event_part,
            'billing' => $billing
        ];
        return response()->json($response);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id, $event_id)
    {
        StateParticipant::where([['event_id', $event_id], ['participant_id', $id]])->delete();
        EventParticipant::where([['event_id', $event_id], ['participant_id', $id]])->delete();
    }

    public function getParticipantQR($data, $eventId)
    {
        $eventIdInt = intval($eventId);
        $decodedData = base64_decode($data);
        $decodedData = json_decode($decodedData, true);
        $event_id = $decodedData['event_id'];
        $participant_id = $decodedData['participant_id'];
        $event_date = EventN::where('id', '=', $event_id)->first();
        if ($eventIdInt === $event_id) {
            if ($event_date->date === date('Y-m-d')) {
                $participants = Participant::where('id', $participant_id)->first();
                $state = StateParticipant::where([
                    ['event_id', $event_id], ['participant_id', $participant_id]
                ])->orderBy('created_at', 'desc')->first();

                $event_part = EventParticipant::where([
                    ['event_id', '=', $event_id],
                    ['participant_id', '=', $participant_id]
                ])->first();

                if ($event_date->date === date('Y-m-d')) {
                    if ($state->state === 'Asistente') {
                        $response = [
                            'message' => 'El participante ya es asistente en el evento.'
                        ];
                    } else {
                        if ($state->state === 'Inscrito') {
                            StateParticipant::Create([
                                'event_id' => $event_id,
                                'participant_id' => $participant_id,
                                'user_id' => Auth::user()->id,
                                'state' => 'Asistente'
                            ]);
                            $state = StateParticipant::where([
                                ['event_id', '=', $event_id],
                                ['participant_id', '=', $participant_id]
                            ])->orderBy('created_at', 'desc')->first();
                            $response = [
                                'participants' => $participants,
                                'state' => $state,
                                'event_part' => $event_part,
                                'event' => $event_date,
                                'message' => 'ok',
                            ];
                        } else {
                            $response = [
                                'message' => 'El participante no está inscrito en el evento.'
                            ];
                        }
                    }
                } else {
                    $response = [
                        'message' => 'El código QR no pertenece al evento'
                    ];
                }
            } else {
                $response = [
                    'message' => 'El evento no está programado para el día de hoy.'
                ];
            }
        } else {
            $response = [
                'message' => 'El código QR no pertenece a este evento.'
            ];
        }


        return response()->json($response);
    }

    public function stateParticipant($participant, $event)
    {
        StateParticipant::Create([
            'event_id' => $event,
            'participant_id' => $participant,
            'user_id' => Auth::user()->id,
            'state' => 'Asistente'
        ]);
        $this->generateQR($event, $participant);
    }
    public function stateParticipantManual(Request $request)
    {
        foreach ($request->participant as $participant) {
            $state = StateParticipant::where([
                ['event_id', $request->event_id],
                ['participant_id', $participant['id']]
            ])->orderBy('created_at', 'desc')->first();

            if ($state->state !== $request->state) {
                $staUpd = StateParticipant::Create([
                    'event_id' => $request->event_id,
                    'participant_id' => $participant['id'],
                    'user_id' => Auth::user()->id,
                    'state' => $request->state
                ]);
                if ($staUpd->state === 'Inscrito') {
                    $this->generateQR($request->event_id, $participant['id']);
                    $this->sendQrEmailParticipant($request->event_id, $participant['id']);
                }
            }
        }
    }
    public function generateQR($event_id, $participant_id)
    {
        $fileName = 'qr_' . $event_id . '_' . $participant_id . '.png';
        $data = [
            'event_id' => $event_id,
            'participant_id' => $participant_id,
        ];
        $data = base64_encode(json_encode($data));

        QrCode::format('png')->size(300)->margin(2)->generate($data, '../public/support/qrParticipant/' . $fileName);

        EventParticipant::updateOrCreate(
            [
                'event_id' => $event_id,
                'participant_id' => $participant_id
            ],
            [
                'qr' => $fileName,
            ]
        );
    }
    function dateToSpanishWords($date)
    {
        // Separar la fecha en partes: día, mes y año
        $parts = explode("-", $date);
        $day = $parts[2];
        $month = $parts[1];
        $year = $parts[0];

        // Definir arreglos de nombres de días, meses y años en español
        $dias = ["domingo", "lunes", "martes", "miércoles", "jueves", "viernes", "sábado"];
        $meses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

        // Convertir la fecha a letras
        $fecha_letras = $day . " de " . $meses[(int)$month] . " de " . $year;

        return $fecha_letras;
    }

    public function certificateParticipant($data)
    {
        $data = json_decode(base64_decode($data));
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['success' => false, 'message' => 'Datos JSON inválidos.'], 400);
        }

        $event_data = EventN::find($data->event_id);
        if (!$event_data) {
            return response()->json(['success' => false, 'message' => 'Evento no encontrado.'], 404);
        }

        foreach ($data->participant as $part) {
            $state = StateParticipant::where([
                ['event_id', $event_data->id],
                ['participant_id', $part->id]
            ])->orderBy('created_at', 'desc')->first();
            if ($state->state === "Asistente") {
                $participant = Participant::find($part->id);
                if (!defined('FPDF_FONTPATH')) {
                    define('FPDF_FONTPATH', public_path('fontsFpdf/'));
                }
                $this->fpdf = new Fpdf();  // Inicializar una nueva instancia de FPDF para cada participante
                $this->fpdf->AddFont('opensans', 'B', 'OpenSans-ExtraBold.php');
                $this->fpdf->AddFont('opensansemi', 'B', 'OpenSans-SemiBold.php');
                $this->fpdf->AddFont('calibri_light', '', 'calibri-light.php');
                $this->fpdf->AddPage('L', 'A4'); // A4 horizontal
                $this->fpdf->Image('assets/img/certificate/certificate.png', 0, 0, 297, 210);
                $this->fpdf->SetFont('opensans', 'B', 48);
                $this->fpdf->SetTextColor(0, 0, 0);
                $this->fpdf->Cell(0, 18, '', 0, 1, 'C');
                $this->fpdf->Cell(0, 15, utf8_decode('CERTIFICADO DE'), 0, 1, 'C');
                $this->fpdf->Cell(0, 24, utf8_decode('ASISTENCIA'), 0, 1, 'C');
                $this->fpdf->SetFont('calibri_light', '', 20);
                $this->fpdf->SetTextColor(58, 58, 54);
                $this->fpdf->Cell(0, 15, 'Entregamos el presente certificado a:', 0, 1, 'C');
                $this->fpdf->Cell(0, 10, '', 0, 1, 'C');
                $this->fpdf->SetFont('opensansemi', 'B', 30);
                $this->fpdf->SetTextColor(0, 0, 0);
                $this->fpdf->MultiCell(0, 10, utf8_decode($participant->name . ' ' . $participant->lastname), 0, 'C');
                $this->fpdf->Cell(0, 10, '', 0, 1, 'C');
                $this->fpdf->SetFont('calibri_light', '', 20);
                $this->fpdf->SetTextColor(58, 58, 54);
                $this->fpdf->MultiCell(0, 8, utf8_decode('Por su asistencia a ' . $event_data->name), 0, 'C');
                $this->fpdf->Cell(0, 40, '', 0, 1, 'C');
                $ximage_sd = $this->fpdf->GetX();
                $yimage_se = $this->fpdf->GetY();
                $this->fpdf->Image('support/logoEvent/' . $event_data->logo, $ximage_sd + 118, $yimage_se - 35, 40, 28);

                $this->fpdf->Cell(0, 10, utf8_decode('Celebrado el día ') . $this->dateToSpanishWords($event_data->date), 0, 1, 'C');
                $name_pdf = "certificate_" . $event_data->id . "_" . $participant->id . ".pdf";
                $filePath = public_path('support/temp/' . $name_pdf);
                $this->fpdf->Output($filePath, 'F');
                $sql = EmailMod::first();
                if ($sql) {
                    $data = [
                        "fromMail" => $sql->email,
                        "userMail" => $participant->email,
                        "name" => $participant->name,
                        "date" => $event_data->date,
                        "hour" => $event_data->hour,
                        "nameEvent" => $event_data->name,
                        "logo" => $event_data->logo,
                        "fileCertificate" => $name_pdf,
                        "year" => date("Y")
                    ];

                    $mail = new PHPMailer(true);
                    $mail->CharSet = 'UTF-8';
                    $asunto = 'Certificado de Participación: ' . $data['nameEvent'];
                    $body = '<!DOCTYPE html>
                    <html lang="es">
                    <head>
                        <meta charset="UTF-8">
                    </head>
                    <body style="font-family: Arial">
                        <table width="100%">
                            <tbody style="color: #3d4852; height: auto; line-height: 1.4; margin: 0px; width: 799px; overflow: visible;">
                                <tr>
                                    <td class="header" style="padding: 25px 0px; text-align: center; background: #f8fafc"><img src="' . url('') . '/support/logoEvent/' .  $data['logo'] . '" alt="Logo Evento" width="10%" max-width="100%"></td>
                                </tr>
                                <tr>
                                    <td class="body" style="padding: 35px;" style="background-color: rgb(255, 255, 255);">
                                        <table class="inner-body" style="background-color: rgb(255, 255, 255); margin: 0px auto; padding: 0px; width: 700px;" align="center">
                                            <tbody>
                                                <tr></tr>
                                                <td class="content-cell">
                                                    <b>
                                                        <h3>Estimado/a ' . $data['name'] . ',</h3>
                                                    </b>
                                                    <p>Adjuntamos su certificado de asistencia correspondiente al evento: <b>' . $data['nameEvent'] . '</b> </p>
                                                    <p><b>Un saludo</b></p>
                                                </td>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="header" style="padding: 25px 0px; text-align: center; background: #f8fafc"></td>
                                </tr>
                            </tbody>
                        </table>
                    </body>
                    </html>';
                    $ss = $sql->SMTPAuth === 1 ? true : false;

                    $mail->CharSet = 'UTF-8';
                    $mail->IsSMTP();
                    $mail->SMTPAuth = $ss;
                    $mail->SMTPSecure = $sql->SMTPSecure;
                    $mail->Host = $sql->host;
                    $mail->Port       = $sql->port;
                    $mail->Username = $sql->email;
                    $mail->Password = $sql->password;
                    $mail->setFrom($sql->email, $sql->from);
                    $mail->addAddress($data['userMail']);
                    $mail->addAttachment(public_path('support/temp/' . $name_pdf));

                    $mail->isHTML(true);
                    $mail->Subject = $asunto;
                    $mail->Body = $body;
                    if (!$mail->Send()) {
                        $resultsend = $mail->ErrorInfo;
                    }
                } else {
                    return response()->json([
                        'message' => 'Debes configurar el correo para poder enviar el certificado.', 'success' => false
                    ], 422);
                }
                unlink($filePath);
            }
        }
        return response()->json(['success' => true, 'message' => 'Certificados enviados'], 201);
    }

    public function tagParticipant($data)
    {
        $data = json_decode(base64_decode($data));
        $event_data = EventN::where('id', '=', $data->event_id)->first();
        foreach ($data->participant as $part) {
            $state = StateParticipant::where([
                ['event_id', $data->event_id],
                ['participant_id', $part->id]
            ])->orderBy('created_at', 'desc')->first();
            $billing = Billing::where('participant_id', $part->id)->first();
            if ($state && $state->state === "Inscrito") {
                if ($data->qr === false) {
                    $participant = Participant::where('id', $part->id)->first();
                    $this->fpdf->SetFont('arial', 'B', 11);
                    $this->fpdf->AddPage("L", ['29', '90']);
                    // Obtener solo los primeros 40 caracteres de $name
                    $name = substr($participant->name . ' ' . $participant->lastname, 0, 40);
                    $textWidth = $this->fpdf->GetStringWidth(utf8_decode($name));
                    $xPos = ($this->fpdf->GetPageWidth() - $textWidth) / 2;
                    $this->fpdf->Text($xPos, 12, utf8_decode($name));
                    // Obtener solo los primeros 40 caracteres de $enterprise
                    if($billing){
                        $this->fpdf->SetFont('arial', 'B', 10);
                        $enterprise = substr($billing->company_name, 0, 40);
                        $textWidth = $this->fpdf->GetStringWidth(utf8_decode($enterprise));
                        $xPos = ($this->fpdf->GetPageWidth() - $textWidth) / 2;
                        $this->fpdf->Text($xPos, 18, utf8_decode($enterprise));
                    }
                } else {
                    $participant = Participant::where('id', $part->id)->first();
                    $this->fpdf->SetFont('arial', 'B', 11);
                    $this->fpdf->AddPage("L", ['60', '86']);
                    $fileName = 'qr_' . $event_data->id . '_' . $participant->id . '.png';
                    // Obtener solo los primeros 40 caracteres de $name
                    $name = substr($participant->name . ' ' . $participant->lastname, 0, 40);
                    $textWidth = $this->fpdf->GetStringWidth(utf8_decode($name));
                    $xPos = ($this->fpdf->GetPageWidth() - $textWidth) / 2;
                    $ximage_sd = $this->fpdf->GetX();
                    $yimage_se = $this->fpdf->GetY();
                    $this->fpdf->Image('support/qrParticipant/' . $fileName, $ximage_sd + 18, $yimage_se + 10, 30, 30);
                    $this->fpdf->Text($xPos, 12, utf8_decode($name));
                    if($billing){
                        $this->fpdf->SetFont('arial', 'B', 10);
                        $enterprise = substr($billing->company_name, 0, 40);
                        $textWidth = $this->fpdf->GetStringWidth(utf8_decode($enterprise));
                        $xPos = ($this->fpdf->GetPageWidth() - $textWidth) / 2;
                        $this->fpdf->Text($xPos, 18, utf8_decode($enterprise));
                    }
                }
            }
        }
        $name_pdf = "tagEvent_" . $event_data->id . ".pdf";
        $this->fpdf->Output($name_pdf, 'I');
        exit;
    }
    public function getParticipantAirtable()
    {
        $dApi = EmailMod::first();
        //$api = 'https://api.airtable.com/v0/appIYWke7RkDgXNpK/NOVED_EVENTO_01?view=Participantes';
        if ($dApi) {
            $api = $dApi->api.'?view='.$dApi->view;
            $client = new Client();
            $headers = [
                //'Authorization' => 'Bearer patwwdbEFGweLsRXq.590202b3c8556349b089d371a3be5dbb48df8ef71700161c6e00a54304aaf1fa', // Reemplaza TU_TOKEN_AQUÍ con tu token
                'Authorization' => 'Bearer '.$dApi->token,
                'Content-Type' => 'application/json',
            ];
            try {
                DB::beginTransaction();
                $response = $client->get($api, [
                    'headers' => $headers,
                ]);
                $dataApi = $response->getBody()->getContents();
                $parsedData = json_decode($dataApi, true);

                if (!empty($parsedData)) {
                    foreach ($parsedData["records"] as $participant) {
                        $id = $participant['id'];
                        $name = isset($participant['fields']['Nombre']) ? $participant['fields']['Nombre'] : null;
                        $lastname = isset($participant['fields']['Apellidos']) ? $participant['fields']['Apellidos'] : null;
                        $email = isset($participant['fields']['Mail']) ? $participant['fields']['Mail'] : null;
                        $dni = isset($participant['fields']['DNI']) ? $participant['fields']['DNI'] : null;
                        $phone = isset($participant['fields']['Telefono']) ? $participant['fields']['Telefono'] : null;
                        $event = isset($participant['fields']['idEvento']) ? $participant['fields']['idEvento'] : null;
                        $novedExist = isset($participant['fields']['Noved']) ? $participant['fields']['Noved'] : null;
                        $country = isset($participant['fields']['Pais']) ? $participant['fields']['Pais'] : null;
                        $province = isset($participant['fields']['Provincia']) ? $participant['fields']['Provincia'] : null;
                        $visitor_typology = isset($participant['fields']['Tipologia del visitante']) ? $participant['fields']['Tipologia del visitante'] : null;
                        $company_name = isset($participant['fields']['Nombre o Razon social']) ? $participant['fields']['Nombre o Razon social'] : null;
                        $cif_nif = isset($participant['fields']['CIF/NIF (facturacion)']) ? $participant['fields']['CIF/NIF (facturacion)'] : null;
                        $billing_email = isset($participant['fields']['Mail (facturacion)']) ? $participant['fields']['Mail (facturacion)'] : null;
                        $billing_address = isset($participant['fields']['Domicilio (facturacion)']) ? $participant['fields']['Domicilio (facturacion)'] : null;
                        $billing_cp = isset($participant['fields']['CP (facturacion)']) ? $participant['fields']['CP (facturacion)'] : null;
                        $billing_locality = isset($participant['fields']['Localidad (facturacion)']) ? $participant['fields']['Localidad (facturacion)'] : null;
                        $billing_province = isset($participant['fields']['Provincia (facturacion)']) ? $participant['fields']['Provincia (facturacion)'] : null;
                        $billing_country = isset($participant['fields']['Pais (facturacion)']) ? $participant['fields']['Pais (facturacion)'] : null;

                        $existingEvent = EventN::where('id', $event)->first();
                        if ($existingEvent) {
                            if ($name !== null && $lastname !== null && $dni !== null && $event !== null && $email !== null && $novedExist === null) {
                                $existingPart = Participant::where('dni', $dni)->exists();
                                //Log::info('existingPart', ['$existingPart' => $existingPart]);
                                if ($existingPart) {
                                    Participant::where('dni', $dni)->update([
                                        'name' => $name,
                                        'lastname' => $lastname,
                                        'email' => $email,
                                        'phone' => $phone,
                                        'country' => $country,
                                        'province' => $province,
                                        'visitor_typology' => $visitor_typology
                                    ]);
                                    $participant = Participant::where('dni', $dni)->first();
                                } else {
                                    $participant = Participant::Create([
                                        'name' => $name,
                                        'lastname' => $lastname,
                                        'email' => $email,
                                        'dni' => $dni,
                                        'phone' => $phone,
                                        'country' => $country,
                                        'province' => $province,
                                        'visitor_typology' => $visitor_typology,
                                        'user_id' => Auth::check() ? Auth::user()->id : null,
                                    ]);
                                }
                                Billing::updateOrCreate(
                                    [
                                        'participant_id' => $participant->id
                                    ],
                                    [
                                        'company_name' => $company_name,
                                        'cif_nif' => $cif_nif,
                                        'billing_email' => $billing_email,
                                        'billing_address' => $billing_address,
                                        'billing_cp' => $billing_cp,
                                        'billing_locality' => $billing_locality,
                                        'billing_province' => $billing_province,
                                        'billing_country' => $billing_country,
                                        'participant_id' => $participant->id,
                                        'user_id' => Auth::check() ? Auth::user()->id : null,
                                    ]
                                );
                                $this->setParticipantAirtable($id, $existingEvent->name);
                                $state = StateParticipant::where([
                                    ['event_id', $event],
                                    ['participant_id', $participant->id]
                                ])->orderBy('created_at', 'desc')->first();

                                $statePre = false;
                                if (!$state || ($state->state !== 'Preinscrito' && $state->state !== 'Asistente')) {
                                    StateParticipant::Create([
                                        'event_id' => $event,
                                        'participant_id' => $participant->id,
                                        'user_id' => Auth::check() ? Auth::user()->id : null,
                                        'state' => 'Preinscrito'
                                    ]);
                                    $statePre = true;
                                }
                                $inscrito = StateParticipant::where([['event_id', $event], ['participant_id', $participant->id]])->orderBy('created_at', 'desc')->first();

                                if ($inscrito->state === 'Inscrito') {
                                    $fileName = 'qr_' . $event . '_' . $participant->id . '.png';
                                    $data = [
                                        'event_id' => $event,
                                        'participant_id' => $participant->id,
                                    ];
                                    $data = base64_encode(json_encode($data));
                                    $filePath = public_path('support/qrParticipant/' . $fileName);
                                    QrCode::format('png')->size(300)->margin(2)->generate($data, $filePath);
                                } else {
                                    $qr_exist = EventParticipant::where([['event_id', $event], ['participant_id', $participant->id]])->first();
                                    if ($qr_exist) {
                                        $fileName = $qr_exist->qr;
                                    } else {
                                        $fileName = null;
                                    }
                                }
                                $evenExis = EventParticipant::where([['event_id', $event],['participant_id', $participant->id]])->exists();
                                EventParticipant::updateOrCreate(
                                    [
                                        'event_id' => $event,
                                        'participant_id' => $participant->id
                                    ],
                                    [
                                        'event_id' => $event,
                                        'participant_id' => $participant->id,
                                        'qr' => $fileName
                                    ]
                                );

                                if ($statePre === true && $evenExis === false) {
                                    $sql = EmailMod::first();
                                    if ($sql) {
                                        $eventData = EventN::where('id', $event)->first();
                                        $data = [
                                            "userMail" => $participant->email,
                                            "name" => $participant->name,
                                            "date" => $eventData->date,
                                            "hour" => $eventData->hour,
                                            "nameEvent" => $eventData->name,
                                            "logo" => $eventData->logo,
                                            "year" => date("Y")
                                        ];
                                        $mail = new PHPMailer(true);
                                        $mail->CharSet = 'UTF-8';
                                        $asunto = 'Confirmación de Inscripción: ¡Bienvenido/a!';
                                        $body = '<!DOCTYPE html>
                                        <html lang="es">
                                        <head>
                                            <meta charset="UTF-8">
                                        </head>
                                        <body style="font-family: Arial">
                                            <table width="100%">
                                                <tbody style="color: #3d4852; height: auto; line-height: 1.4; margin: 0px; width: 799px; overflow: visible;">
                                                    <tr>
                                                        <td class="header" style="padding: 25px 0px; text-align: center; background: #f8fafc"><img src="' . url('') . '/support/logoEvent/' .  $data['logo'] . '" alt="Logo Evento" width="10%" max-width="100%"></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="body" style="padding: 35px;" style="background-color: rgb(255, 255, 255);">
                                                            <table class="inner-body" style="background-color: rgb(255, 255, 255); margin: 0px auto; padding: 0px; width: 700px;" align="center">
                                                                <tbody>
                                                                    <tr></tr>
                                                                    <td class="content-cell">
                                                                        <b>
                                                                            <h3>Estimado/a ' . $data['name'] . ',</h3>
                                                                        </b>
                                                                        <p>Hemos recibido correctamente su solicitud para asistir al evento: <b>' . $data['nameEvent'] . '</b> </p>
                                                                        <p>En breve confirmaremos su asistencia y le haremos llegar su invitación. </p>
                                                                        <p><b>Muchas gracias</b></p>
                                                                    </td>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="header" style="padding: 25px 0px; text-align: center; background: #f8fafc"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </body>
                                        </html>';
                                        $ss = $sql->SMTPAuth === 1 ? true : false;
                                        $mail->CharSet = 'UTF-8';
                                        $mail->IsSMTP();
                                        $mail->SMTPAuth = $ss; //true
                                        $mail->SMTPSecure = $sql->SMTPSecure; //tls
                                        $mail->Host = $sql->host; //smtp.gmail.com
                                        $mail->Port       = $sql->port; //587
                                        $mail->Username = $sql->email; //noreply@aunnait.es
                                        $mail->Password = $sql->password; //wzuogblenxhzrkeo
                                        $mail->setFrom($sql->email, $sql->from);
                                        $mail->addAddress($data['userMail']);
                                        $mail->isHTML(true);
                                        $mail->Subject = $asunto;
                                        $mail->Body = $body;
                                        if (!$mail->Send()) {
                                            $resultsend = $mail->ErrorInfo;
                                        }
                                    } else {
                                        return response()->json([
                                            'message' => 'Debes configurar el correo para poder enviar el certificado.', 'success' => false
                                        ], 422);
                                    }
                                }
                            }
                        }
                    }
                } else {
                    throw new \Exception('No se pudieron obtener los datos de participantes de la API.');
                }
                DB::commit();
                return response()->json(['success' => true, 'message' => 'Participantes sincronizados'], 201);
            } catch (\Exception $e) {
                DB::rollback();
                Log::error('error', ['error' => $e]);
                return response()->json(['error' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'Debes configurar la api en el módulo de configuración.'], 500);
        }
    }

    public function setParticipantAirtable($id, $event)
    {
        $data = EmailMod::first();
        //$api = 'https://api.airtable.com/v0/appIYWke7RkDgXNpK/NOVED_EVENTO_01';
        if ($data){
            $client = new Client();

            $headers = [
                'Authorization' => 'Bearer '.$data->token,
                'Content-Type' => 'application/json',
            ];

            $body = json_encode([
                'records' => [
                    [
                        'id' => $id,
                        'fields' => [
                            'Noved' => true,
                            'Evento' => $event
                        ]
                    ]
                ]
            ]);

            $response = $client->patch($data->api, [
                'headers' => $headers,
                'body' => $body,
            ]);

            $dataApi = $response->getBody()->getContents();
            $parsedData = json_decode($dataApi, true);

            // Puedes devolver los datos parseados si necesitas hacer algo con ellos
            return $parsedData;
        }
    }

    public function sendAccreditation($data)
    {
        $data = json_decode(base64_decode($data));
        $eventData = EventN::where('id', '=', $data->event_id)->first();
        foreach ($data->participant as $part) {
            $state = StateParticipant::where([
                ['event_id', $eventData->id],
                ['participant_id', $part->id]
            ])->orderBy('created_at', 'desc')->first();

            if ($state && $state->state === "Inscrito") {
                $this->sendQrEmailParticipant($eventData->id, $part->id);
            }
        }
        return response()->json(['success' => true, 'message' => 'Acreditaciones enviadas'], 201);
    }


    public function sendQrEmailParticipant($event_id, $participant_id)
    {
        $eventData = EventN::where('id', $event_id)->first();
        $participant = Participant::where('id', $participant_id)->first();
        $qr = EventParticipant::where([
            ['event_id', $event_id],
            ['participant_id', $participant_id]
        ])->first();
        $filePath = public_path('support/qrParticipant/' . $qr->qr);
        $sql = EmailMod::first();
        if ($sql) {
            $data = [
                "userMail" => $participant->email,
                "name" => $participant->name,
                "date" => $eventData->date,
                "hour" => $eventData->hour,
                "nameEvent" => $eventData->name,
                "logo" => $eventData->logo,
                "fileQR" => $eventData->logo,
                "year" => date("Y")
            ];
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $asunto = 'Acreditación: ' . $data['nameEvent'];
            $body = '<!DOCTYPE html>
                    <html lang="es">
                    <head>
                        <meta charset="UTF-8">
                    </head>
                    <body style="font-family: Arial">
                        <table width="100%">
                            <tbody style="color: #3d4852; height: auto; line-height: 1.4; margin: 0px; width: 799px; overflow: visible;">
                                <tr>
                                    <td class="header" style="padding: 25px 0px; text-align: center; background: #f8fafc"><img src="' . url('') . '/support/logoEvent/' . $data['logo'] . '" alt="Logo Evento" width="10%" max-width="100%"></td>
                                </tr>
                                <tr>
                                    <td class="body" style="padding: 35px;" style="background-color: rgb(255, 255, 255);">
                                        <table class="inner-body" style="background-color: rgb(255, 255, 255); margin: 0px auto; padding: 0px; width: 700px;" align="center">
                                            <tbody>
                                                <tr></tr>
                                                <td class="content-cell">
                                                    <b>
                                                        <h3>Estimado/a ' . $data['name'] . ',</h3>
                                                    </b>
                                                    <p>Hemos confirmado correctamente su asistencia.</p>
                                                    <p>Le hacemos llegar su código QR de acceso al evento: <b>' . $data['nameEvent'] . '</b>, el día<b>
                                                            ' . $data['date'] . '</b> a las <b>' . $data['hour'] . '</b> </p>
                                                    <p><b>A su llegada, muestre este código QR al personal de la entrada. Muchas gracias.</b></p>

                                                </td>
                                                <tr></tr>
                                                <td class="content-cell">
                                                <img src="' . url('') . '/support/qrParticipant/' . $qr->qr . '" alt="QR Evento" width="100px" height="100px">
                                                </td>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="header" style="padding: 25px 0px; text-align: center; background: #f8fafc"></td>
                                </tr>
                            </tbody>
                        </table>
                    </body>
                    </html>';
            $ss = $sql->SMTPAuth === 1 ? true : false;

            $mail->CharSet = 'UTF-8';
            $mail->IsSMTP();
            $mail->SMTPAuth = $ss;
            $mail->SMTPSecure = $sql->SMTPSecure;
            $mail->Host = $sql->host;
            $mail->Port       = $sql->port;
            $mail->Username = $sql->email;
            $mail->Password = $sql->password;
            $mail->setFrom($sql->email, $sql->from);
            $mail->addAddress($data['userMail']);
            $mail->addAttachment($filePath);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $body;
            if (!$mail->Send()) {
                $resultsend = $mail->ErrorInfo;
            }
        } else {
            return response()->json([
                'message' => 'Debes configurar el correo para poder enviar el certificado.', 'success' => false
            ], 422);
        }
    }
}
