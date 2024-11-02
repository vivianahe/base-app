<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ParticipantsController;
use App\Models\User; // Importa el modelo de usuario
use Illuminate\Support\Facades\Auth;

class MailInscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:mail-inscriptions';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza participantes de un evento desde airtable API';

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Llama al método del controlador
        $controller = new ParticipantsController();
        $controller->getParticipantAirtable();
        return 0;
    }
}
