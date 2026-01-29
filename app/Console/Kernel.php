<?php

namespace  App\Console ;

use  Illuminate\Console\Scheduling\Schedule ;
use  Illuminate\Foundation\Console\Kernel  as  ConsoleKernel ;

class  Kernel  extends  ConsoleKernel
{
    /**
     * Define o agendamento de comandos da aplicação.
     */
    protected  function  schedule ( Schedule $schedule ): void
    {
        // $schedule->command('inspire')->hourly();
        // Adicione suas tarefas agendadas aqui.
    }

    /**
     * Registra os comandos para a aplicação.
     */
    protected  function  commands ( ): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
