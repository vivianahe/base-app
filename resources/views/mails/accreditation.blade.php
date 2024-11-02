<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
</head>

<body style="font-family: Arial">

    <table width="100%">
        <tbody style='color: #3d4852; height: auto; line-height: 1.4; margin: 0px; width: 799px; overflow: visible;'>
            <tr>
                <td class="header" style='padding: 25px 0px; text-align: center; background: #f8fafc'><img src="{{ asset('support/logoEvent/' . $data['logo']) }}" alt="Logo Evento" width="10%" max-width="100%"></td>
            </tr>
            <tr>
                <td class="body" style='padding: 35px;' style='background-color: rgb(255, 255, 255);'>
                    <table class="inner-body" style=' background-color: rgb(255, 255, 255); margin: 0px auto; padding: 0px; width: 700px;' align="center">
                        <tbody>
                            <tr></tr>
                            <td class="content-cell">
                                <b>
                                    <h3>Estimado/a {{ $data['name'] }},</h3>
                                </b>
                                <p>Adjuntamos tu acreditación para acceder al evento <b>{{ $data['nameEvent'] }}</b>, que se llevará a cabo el <b>
                                        {{ $data['date'] }} </b> a las <b> {{ $data['hour'] }}</b>. ¡Esperamos verte allí! </p>
                                <p><b>¡Saludos!</b></p>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="header" style='padding: 25px 0px; text-align: center;'><img src="{{ asset('support/qrParticipant/' . $data['fileQR']) }}" alt="Logo Evento" width="10%" max-width="100%"></td>
            </tr>
            <tr><td class="header" style='padding: 25px 0px; text-align: center; background: #f8fafc'></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
