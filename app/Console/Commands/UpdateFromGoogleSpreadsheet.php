<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\UtmParam;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
    
    private static function processPhone($phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        
        if (!$phone) {
            return $phone;
        }
        
        if (strlen($phone) === 11 && $phone[0] === '8') {
            $phone[0] = '7';
        }
        return $phone;
    }
    
    private static function getProductType($products): string
    {
        if (mb_strpos(mb_strtolower($products), 'коррекция')) {
            return 'Коррекция';
        }
        
        if (mb_strpos(mb_strtolower($products), 'силовой')) {
            return 'Силовой';
        }
        
        if (mb_strpos(mb_strtolower($products), 'введение')) {
            return 'Введение';
        }
        
        if (mb_strpos(mb_strtolower($products), 'архив')) {
            return 'Архив';
        }
        
        return 'Другое';
        
    }

    private static function getProductLength($products): string
    {
        if (mb_strpos(mb_strtolower($products), '12')) {
            return '12';
        }

        if (mb_strpos(mb_strtolower($products), '6')) {
            return '6';
        }

        if (mb_strpos(mb_strtolower($products), '3')) {
            return '3';
        }
        
        return '1';

    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sheet = Sheets::spreadsheet(env('SPREADSHEET_ID'))->sheet('Лист1');

        $rows = $sheet->get();
        $header = $rows->pull(0);
        $values = Sheets::collection(header: $header, rows: $rows);
        
        $crmHistoriesCount = DB::table('crm_history')->count();
        
        $countNew = 0;
        foreach ($values as $key => $value) {
            if ($key < $crmHistoriesCount) continue;

            $countNew++;

            $this->info('Row '. $key);
            
            $crmHistory = DB::table('crm_history')
                ->where('email', $value['Email'])
                ->where('paymentid', $value['paymentid'])
                ->where('products', $value['products'])
                ->where('sent', $value['sent'] ? Carbon::parse($value['sent'])->toDateTimeString() : null);
            
            if ($crmHistory->exists()) continue;
            
            DB::table('crm_history')->insert([
                'email' => $value['Email'],
                'paymentid' => $value['paymentid'],
                'products' => $value['products'],
                'sent' => Carbon::parse($value['sent'])->toDateTimeString(),
                'name' => $value['Name'],
                'phone' => $value['Phone'],
                'paymentsystem' => $value['paymentsystem'],
                'orderid' => $value['orderid'],
                'price' => (float)$value['price'],
                'promocode' => $value['promocode'],
                'discount' => (float)$value['discount'],
                'subtotal' => (float)$value['subtotal'],
                'cookies' => $value['cookies'],
                'currency' => $value['Currency'],
                'payment_status' => $value['Payment status'],
                'referer' => $value['referer'],
                'formid' => $value['formid'],
                'form_name' => $value['Form name'],
                'requestid' => $value['requestid'],
                'utm_source' => $value['utm_source'],
                'utm_medium' => $value['utm_medium'],
                'utm_campaign' => $value['utm_campaign'],
                'utm_term' => $value['utm_term'],
                'utm_content' => $value['utm_content'],
                'input_' => $value['Input'],
                'textarea' => $value['Textarea'],
                'ma_name' => $value['ma_name'],
                'ma_email' => $value['ma_email'],
                'add_time' => now(),
            ]);
            
            if ((float)$value['price'] < 100) continue;

            $customer = Customer::updateOrCreate(
                [
                    'email' => strtolower($value['Email'])
                ], 
                [
                    'customer_name' => $value['Name'], 
                    'phone' => self::processPhone($value['Phone']),
                ]);

            $productName = trim(explode('-', $value['products'])[0]);
            
            $product = Product::updateOrCreate([
                'product_name' => $productName,
                'product_type' => self::getProductType($value['products']),
                'product_length' => self::getProductLength($productName),
                'product_price' => trim(explode('=', $value['products'])[1]),
            ]);
            
            $utm = array_filter([
                'utm_source' => $value['utm_source'],
                'utm_medium' => $value['utm_medium'],
                'utm_campaign' => $value['utm_campaign'],
                'utm_term' => $value['utm_term'],
                'utm_content' => $value['utm_content'],
            ]);
            
            if ($utm) {
               $utmParam = UtmParam::updateOrCreate($utm);
            }

            Transaction::create([
                'form_id' => $value['formid'],
                'form_name' => $value['Form name'],
                'order_id' => $value['orderid'],
                'payment_system' => $value['paymentsystem'],
                'payment_id' => $value['paymentid'],
                'subtotal' => (int)$value['subtotal'],
                'promocode' => $value['promocode'],
                'discount' => (int)$value['discount'],
                'price' => (int)$value['price'],
                'currency' => $value['Currency'],
                'payment_status' => $value['Payment status'],
                'referer' => $value['referer'],
                'transaction_date' => Carbon::parse($value['sent'])->toDateTimeString(),
                
                'customer_id' => $customer->customer_id,
                'product_id' => $product->product_id,
                'utm_id' => isset($utmParam) ? $utmParam->utm_id : null,
            ]);
            
            
//            if ($value['exported'] === '2') continue;
//            $updateCell = 'AC' . $key + 1;
//            $sheet->range($updateCell)->update([['2']]);
            
        }
        
        Log::info('New: ' . $countNew);
    }
}
