<?php

namespace App\Console\Commands;


use App\Http\Controllers\Trade\TradeAutoForward as TradeTradeAutoForward;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class TradeAutoForward extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trade:auto-forward';

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
        $controller = App::makeWith(TradeTradeAutoForward::class);        
        $controller->AutoForwardAssistent();
        return 0;
    }
}
