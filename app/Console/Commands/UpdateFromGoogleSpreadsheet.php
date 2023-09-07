<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Follower;
use App\Models\MarketingChannel;
use App\Models\MarketingMetric;
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
        if (mb_strpos(mb_strtolower($products), 'архив') !== false) {
            return 'Архив';
        }

        if (mb_strpos(mb_strtolower($products), 'силовой') !== false) {
            return 'Силовой';
        }
        
        if (mb_strpos(mb_strtolower($products), 'коррекция') !== false) {
            return 'Коррекция';
        }
        
        if (mb_strpos(mb_strtolower($products), 'введение') !== false) {
            return 'Введение';
        }
        
        
        return 'Другое';
        
    }
    
    
    private static function getProductName($products): string
    {
        $separator = mb_strpos($products, '/') !== false ? '/' : '-';
        
        return trim(explode($separator, $products)[0]);
    }
    
    
    private static function getSaleNumber($products): string
    {
        if (mb_strpos($products, '/') === false) {
            return 'без потока';
        }
        
        $afterSlash = explode('/', $products)[1];
        
        return trim(explode('-', $afterSlash)[0]);
    }
    

    private static function getProductLength($products): string
    {
        if (mb_strpos(mb_strtolower($products), '12') !== false) {
            return '12';
        }

        if (mb_strpos(mb_strtolower($products), '6') !== false) {
            return '6';
        }

        if (mb_strpos(mb_strtolower($products), '3') !== false) {
            return '3';
        }
        
        return '1';

    }
    
    private static function strToInt($str): ?int
    {
        if ($str === '') return null;
        
        return (int)preg_replace('/\D/', '', $str);
    }
    
    private static function strToFloat($str): ?float
    {
        if ($str === '') return null;

        return (float)str_replace(',', '.', $str);
    }

    /**
     * Execute the console command.
     */
    private function updateCRM()
    {
        $sheet = Sheets::spreadsheet(env('CRM_SPREADSHEET_ID'))->sheet('Лист1');

        $rows = $sheet->get();
        $header = $rows->pull(0);
        $values = Sheets::collection(header: $header, rows: $rows);

        $crmHistoriesCount = DB::table('crm_history')->count();

        $countNew = 0;
        foreach ($values as $key => $value) {
            if ($key < $crmHistoriesCount) continue;

            $countNew++;

            $this->info('CRM Row ' . $key);

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
                'price' => $this->strToFloat($value['price']),
                'promocode' => $value['promocode'],
                'discount' => $this->strToFloat($value['discount']),
                'subtotal' => $this->strToFloat($value['subtotal']),
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

            $productName = self::getProductName($value['products']);

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

            $saleNumber = self::getSaleNumber($value['products']);

            Transaction::create([
                'form_id' => $value['formid'],
                'sale_number' => $saleNumber,
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
            
        }

        Log::info('CRM New: ' . $countNew);
    }
    
    private function updateMarketing()
    {
        $sheet = Sheets::spreadsheet(env('MARKETING_SPREADSHEET_ID'))->sheet('Реклама');
        $rows = $sheet->get()->slice(2)->values();
        $header = $rows->pull(0);
        
        $values = Sheets::collection(header: $header, rows: $rows);
        
        $marketingHistoriesCount = DB::table('marketing_history')->count();
        $countNew = 0;
        foreach ($values as $key => $value) {
            if ($key < $marketingHistoriesCount) continue;
            
            $this->info('Marketing Row ' . $key);
            
            $actualDate = $value['actual_date'] ? Carbon::parse($value['actual_date'])->toDateString() : null;
            $channel = $value['channel'];
            

            $marketingHistory = DB::table('marketing_history')
                ->where('actual_date', $actualDate)
                ->where('channel', $channel);

            if ($marketingHistory->exists()) continue;

            $countNew++;

            DB::transaction(function () use ($value, $actualDate, $channel) {
                
                $impressions = $this->strToInt($value['impressions']);
                $visits = $this->strToInt($value['visits']);
                $ctr = $this->strToFloat($value['ctr']);
                $cpc = $this->strToFloat($value['cpc']);
                $cr1 = $this->strToFloat($value['cr1']);
                $leads = $this->strToInt($value['leads']);
                $cpl = $this->strToFloat($value[' cpl']);
                $cr2 = $this->strToFloat($value['cr2']);
                $qual_leads = $this->strToInt($value['qual_leads']);
                $cpql = $this->strToFloat($value[' cpql']);

                DB::table('marketing_history')->insert([
                    'actual_date' => $actualDate,
                    'channel' => $channel,
                    'impressions' => $impressions,
                    'visits' => $visits,
                    'ctr' => $ctr,
                    'cpc' => $cpc,
                    'cr1' => $cr1,
                    'leads' => $leads,
                    'cpl' => $cpl,
                    'cr2' => $cr2,
                    'qual_leads' => $qual_leads,
                    'cpql' => $cpql,
                    'add_time' => now(),
                ]);

                $channel = MarketingChannel::updateOrCreate(
                    [
                        'channel_name' => $channel
                    ],
                );

                MarketingMetric::updateOrCreate([
                    'channel_id' => $channel->channel_id,
                    'actual_date' => $actualDate,
                ], [
                    'impressions' => $impressions,
                    'visits' => $visits,
                    'ctr' => $ctr / 100,
                    'cpc' => $cpc,
                    'cr1' => $cr1 / 100,
                    'leads' => $leads,
                    'cpl' => $cpl,
                    'cr2' => $cr2 / 100,
                    'qual_leads' => $qual_leads,
                    'cpql' => $cpql,
                ]);
                
            });
            
        }
        
        Log::info('Marketing New: ' . $countNew);
    }
    
    public function updateFollowers()
    {
        $sheet = Sheets::spreadsheet(env('MARKETING_SPREADSHEET_ID'))->sheet('Подписная база');
        $rows = $sheet->get()->slice(2)->values();
        $header = $rows->pull(0);

        $values = Sheets::collection(header: $header, rows: $rows);

        foreach ($values as $key => $value) {
            
            $this->info('Followers Row ' . $key);
            

            Follower::updateOrCreate([
                'actual_date' => $value['actual_date'] ? Carbon::parse($value['actual_date'])->toDateString() : null,
            ], [
                'add_time' => now(),
                'email' => $value['email'] === '' ? null : $value['email'],
                'tg_bot' => $value['tg_bot'] === '' ? null : $value['tg_bot'],
                'tg_channel' => $value['tg_channel'] === '' ? null : $value['tg_channel'],
                'inst' => $value['inst'] === '' ? null : $value['inst'],
                'inst_er' => round($this->strToFloat($value['inst_er']) * ($value['inst'] ?: 0) / 100),
                'vk' => $value['vk'] === '' ? null : $value['vk'],
                'dzen' => $value['dzen'] === '' ? null : $value['dzen'],
                'app' => $value['app'] === '' ? null : $value['app'],
            ]);

        }
        
    }    
    
    
    public function handle()
    {
        $this->updateMarketing();
        $this->updateFollowers();
        $this->updateCRM();
    }
}
