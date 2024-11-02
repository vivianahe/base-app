<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Address;
class MailCertificate extends Mailable
{
    use Queueable, SerializesModels;
    public $data;
    public $fileCertificate;
    public $fromEmail; // Propiedad para la dirección de correo electrónico del remitente

    /**
     * Create a new message instance.
     */
    public function __construct($data, $fileCertificate, $fromEmail)
    {
        $this->data = $data;
        $this->fileCertificate = $fileCertificate;
        $this->fromEmail = $fromEmail; // Asignar el correo electrónico del remitente
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromEmail, 'Nombre del Remitente'), // Establecer remitente con nombre
            subject: 'Certificado de Participación: ' . $this->data['nameEvent'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.certificate',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->fileCertificate)
        ];
    }
}
