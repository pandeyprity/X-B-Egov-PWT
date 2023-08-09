<?php

namespace App\Console\Commands;

use App\Http\Controllers\Notice\NoticeController;
use App\Repository\Notice\INotice;
use App\Repository\Property\Interfaces\iPropertyDetailsRepo;
use App\Repository\Trade\ITrade;
use App\Repository\Water\Interfaces\iNewConnection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class SendWhatsAppMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $controller = App::makeWith(NoticeController::class, ['INotice' => app(INotice::class),"iPropertyDetailsRepo"=>app(iPropertyDetailsRepo::class),"iNewConnection"=>app(iNewConnection::class),"ITrade"=>app(ITrade::class)]);        
        $controller->openNoticiList(true); 
        return 0;
    }
}
