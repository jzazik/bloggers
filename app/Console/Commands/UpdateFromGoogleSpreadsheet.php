<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Revolution\Google\Sheets\Facades\Sheets;

class UpdateFromGoogleSpreadsheet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-from-google-spreadsheet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        dump(DB::select('SHOW TABLES')); exit;
        $sheet = Sheets::spreadsheet(env('SPREADSHEET_ID'))->sheet('Лист1');

        $rows = $sheet->get();
        $header = $rows->pull(0);
        $values = Sheets::collection(header: $header, rows: $rows);
        
        foreach ($values as $key => $value) {
            if ($value['exported'] === '2') continue;
            
            $updateCell = 'AC' . $key + 1;
            $sheet->range($updateCell)->update([['2']]);
            
            exit;
            
            
            
        }
        dump($values); exit;
        
        $sheet->range('AC2')->update([['1']]);
//    dump($data[0]); exit;
    }
}
