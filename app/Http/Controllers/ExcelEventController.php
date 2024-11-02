<?php

namespace App\Http\Controllers;

use App\Models\EventN;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelEventController extends Controller
{
    public function getEventExport($event)
    {
        $response = Participant::join('event_participants', 'event_participants.participant_id', 'participants.id')
            ->join('events_noved', 'events_noved.id', 'event_participants.event_id')
            ->leftJoin('billing', 'billing.participant_id', 'participants.id')
            ->join('state_participants', function ($join) {
                $join->on('state_participants.participant_id', '=', 'participants.id')
                    ->on('state_participants.event_id', '=', 'events_noved.id')
                    ->whereRaw('state_participants.created_at = (select max(created_at) from state_participants where participant_id = participants.id and event_id = events_noved.id)');
            })
            ->select('participants.*', 'events_noved.name as event', 'events_noved.date as date_evt', 'events_noved.id as event_id', 'state_participants.state',
            'billing.company_name', 'billing.cif_nif', 'billing.billing_email', 'billing.billing_address', 'billing.billing_cp', 'billing.billing_locality', 'billing.billing_province', 'billing.billing_country')
            ->where('events_noved.id', $event)
            ->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $styleArray_body = [
            'font' => [
                'bold' => false,
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $styleArray_header = [
            'font' => [
                'bold' => true,
                'size' => 10,
                'color' => array('rgb' => 'FFFFFF'),
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => '6e0958',
                ],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('rgb' => '000000'),
                ],
            ],
        ];

        $sheet->getStyle('A1')->applyFromArray($styleArray_header);
        $sheet->getStyle('A2')->applyFromArray($styleArray_header);
        $sheet->getStyle('C2')->applyFromArray($styleArray_header);
        $sheet->getStyle('A4:R4')->applyFromArray($styleArray_header);
        $sheet->getStyle("B1:D1")->applyFromArray($styleArray_body);
        $sheet->getStyle("B2")->applyFromArray($styleArray_body);
        $sheet->getStyle("D2")->applyFromArray($styleArray_body);
        $sheet->mergeCells('B1:D1');
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(25);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(28);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('R')->setWidth(30);
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(30);

        //Text bold
        $spreadsheet->getActiveSheet()->getStyle("A1:A2")->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getStyle("C1:C2")->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getStyle("A4:R4")->getFont()->setBold(true);

        $spreadsheet->getActiveSheet()->getStyle('A1:D2')->getAlignment()->setWrapText(true);

        $spreadsheet->getActiveSheet()->getStyle('A4:R' . (sizeof($response) + 4))
            ->getAlignment()->setWrapText(true);

        $sheet->setCellValue('A1', 'Evento');
        $sheet->setCellValue('A2', 'Generado por');
        $evn = EventN::where('id', $event)->first();

        $sheet->setCellValue('B1', $evn->name);
        $sheet->setCellValue('B2',  Auth::user()->name);
        $sheet->setCellValue('C2', 'Fecha');
        $sheet->setCellValue('D2', $evn->date);
        $sheet->setCellValue('A4', '#');
        $sheet->setCellValue('B4', 'DNI');
        $sheet->setCellValue('C4', 'Nombre');
        $sheet->setCellValue('D4', 'Apellidos');
        $sheet->setCellValue('E4', 'Email');
        $sheet->setCellValue('F4', 'Teléfono');
        $sheet->setCellValue('G4', 'Estado');
        $sheet->setCellValue('H4', 'País');
        $sheet->setCellValue('I4', 'Provincia');
        $sheet->setCellValue('J4', 'Tipología del visitante');
        $sheet->setCellValue('K4', 'Nombre o razón social');
        $sheet->setCellValue('L4', 'CIF/NIF (facturación)');
        $sheet->setCellValue('M4', 'Email (facturación)');
        $sheet->setCellValue('N4', 'Domicilio (facturación)');
        $sheet->setCellValue('O4', 'CP (facturación)');
        $sheet->setCellValue('P4', 'Localidad (facturación)');
        $sheet->setCellValue('Q4', 'Provincia (facturación)');
        $sheet->setCellValue('R4', 'País (facturación)');

        $i = 5;
        if (!empty($response)) {
            foreach ($response as $row) {
                $sheet->getStyle('A' . $i . ':R' . $i)->applyFromArray($styleArray_body);
                $sheet->setCellValue('A' . $i, $i - 4);
                $sheet->setCellValue('B' . $i, $row->dni);
                $sheet->setCellValue('C' . $i, $row->name);
                $sheet->setCellValue('D' . $i, $row->lastname);
                $sheet->setCellValue('E' . $i, $row->email);
                $sheet->setCellValue('F' . $i, $row->phone);
                $sheet->setCellValue('G' . $i, $row->state);
                $sheet->setCellValue('H' . $i, $row->country);
                $sheet->setCellValue('I' . $i, $row->province);
                $sheet->setCellValue('J' . $i, $row->visitor_typology);
                $sheet->setCellValue('K' . $i, $row->company_name);
                $sheet->setCellValue('L' . $i, $row->cif_nif);
                $sheet->setCellValue('M' . $i, $row->billing_email);
                $sheet->setCellValue('N' . $i, $row->billing_address);
                $sheet->setCellValue('O' . $i, $row->billing_cp);
                $sheet->setCellValue('P' . $i, $row->billing_locality);
                $sheet->setCellValue('Q' . $i, $row->billing_province);
                $sheet->setCellValue('R' . $i, $row->billing_country);
                $i++;
            }
        }
        $sheet->freezePane('A5');

        $writer = new Xlsx($spreadsheet);
        $filename = 'Participantes_' . $event . '.xlsx';
        header('Content-Disposition: attachment; filename=' . $filename . '');
        header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        $writer->save("php://output");
        exit;
    }
}
