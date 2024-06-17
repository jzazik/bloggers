<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Revolution\Google\Sheets\Facades\Sheets;


class UpdateFromGoogleSpreadsheet extends Command
{

    private $errors = 0;
    private $error_details = [];
    private Model $updateLog;
    private Model $customer;
    private Model $transaction;
    private Model $product;
    private Model $utmParam;
    private Model $follower;
    private Model $marketing_metric;
    private Model $marketing_channel;
    private Model $marketing_history;
    private Model $subscription;
    private Model $refund;
    private Model $installment;
    private Model $crm_history;
    private string $blogger;
    private bool $isKochfit;
    private bool $isKinezio;

    private array $bloggerList = [
        'popovich',
        'kochfit',
        'kinezio'
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-from-google-spreadsheet {blogger}';

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
    
    private function getProductForm($products): string
    {
        if (mb_strpos(mb_strtolower($products), 'подписка') !== false) {
            return 'Подписка';
        }

        if (mb_strpos(mb_strtolower($products), 'продление') !== false || mb_strpos(mb_strtolower($products), 'повторно') !== false) {
            return 'Продление';
        }
        
        return 'База';
        
   }

    private function getProductFormForSubscription($products, $refund = false): string
    {
        if (mb_strpos(mb_strtolower($products), 'подписка') !== false) {
            return 'Подписка';
        }

        if (mb_strpos(mb_strtolower($products), 'продление') !== false) {
            return 'Продление';
        }
        
        return $refund ? 'База' : 'Подписка';

    }

    private function getProductTypeForSubscription($products): string
    {

        if ($this->isKochfit) {

            if (mb_strpos(mb_strtolower($products), 'лайт') !== false || mb_strpos(mb_strtolower($products), 'тандарт') !== false) {
                return 'Красота и здоровье Стандарт';
            }

            if (mb_strpos(mb_strtolower($products), 'премиум') !== false) {
                return 'Красота и здоровье Премиум';
            }

            if (mb_strpos(mb_strtolower($products), 'тестовая неделя') !== false) {
                return 'Красота и здоровье Тестовая неделя';
            }

            if (mb_strpos(mb_strtolower($products), 'мтд') !== false) {
                return 'МТД и дыхание';
            }

            if (mb_strpos(mb_strtolower($products), 'восстановление') !== false) {
                return 'Восстановление после родов';
            }

            if (mb_strpos(mb_strtolower($products), 'активная') !== false) {
                return 'Активная беременность';
            }

            if (mb_strpos(mb_strtolower($products), 'фитнес тур') !== false) {
                return 'Фитнес тур';
            }

        }

        return '';

    }
    
    private function getProductType($products): string
    {
    
        if ($this->isKinezio) {
            if (mb_strpos(mb_strtolower($products), 'фундамент') !== false
                && mb_strpos(mb_strtolower($products), 'с обратной связью') !== false) {
                return 'Фундамент с обратной связью';
            }
            
            if (mb_strpos(mb_strtolower($products), 'фундамент') !== false
                && mb_strpos(mb_strtolower($products), 'без обратной связи') !== false) {
                return 'Фундамент без обратной связи';
            }
            
            if (mb_strpos(mb_strtolower($products), 'анатомия') !== false) {
                return 'Анатомия'; 
            }
            
            if (mb_strpos(mb_strtolower($products), 'пробный') !== false) {
                return 'Пробный';
            }
        } else if ($this->isKochfit) {

            if (mb_strpos(mb_strtolower($products), 'красота и здоровье') !== false 
                && (
                    mb_strpos(mb_strtolower($products), 'лайт') || mb_strpos(mb_strtolower($products), 'тандарт')
                )) {
                return 'Красота и здоровье Стандарт';
            }

            if (mb_strpos(mb_strtolower($products), 'красота и здоровье') !== false 
                && mb_strpos(mb_strtolower($products), 'премиум') !== false) {
                return 'Красота и здоровье Премиум';
            }
            
            if (mb_strpos(mb_strtolower($products), 'красота и здоровье') !== false
                && mb_strpos(mb_strtolower($products), 'тестовая неделя') !== false) {
                return 'Красота и здоровье Тестовая неделя';
            }
            
            if (mb_strpos(mb_strtolower($products), 'мтд') !== false) {
                return 'МТД и дыхание';
            }
                        
            if (mb_strpos(mb_strtolower($products), 'восстановление') !== false) {
                return 'Восстановление после родов';
            }
                       
            if (mb_strpos(mb_strtolower($products), 'активная') !== false) {
                return 'Активная беременность';
            }

            return 'Архив';

        } else {
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
        
        return '';
        
    }
    
    
    private function getProductName($products): string
    {
        if ($this->blogger === 'kochfit') {
            $separator = '-';
        } else {
            $separator = mb_strpos($products, '/') !== false ? '/' : '-';
        }
        
        return trim(explode($separator, $products)[0]);
    }
    
    
    private static function getSaleNumber($products): string
    {
        if (mb_strpos($products, '/') === false) {
            return 0;
        }
        
        $afterSlash = explode('/', $products)[1];
        
        return (int)trim(explode('-', $afterSlash)[0]);
    }
    

    private function getProductLength($products): string
    {
        if ($this->isKinezio) {
            if (mb_strpos(mb_strtolower($products), 'фундамент') !== false) {
                return '6';
            }

            if (mb_strpos(mb_strtolower($products), 'анатомия') !== false) {
                return '1';
            }
            
            if (mb_strpos(mb_strtolower($products), 'пробный') !== false) {
                return '0';
            }
            
            return '0';
        }
        
        if ($this->isKochfit) {
            
            $numbers = preg_replace('/[^0-9]/', '', $products);
            
            if ($numbers) return 30 * $numbers;

            if (mb_strpos(mb_strtolower($products), 'годовой') !== false) {
                return '360';
            }

            if (mb_strpos(mb_strtolower($products), 'ведение тренировки') !== false ||
                mb_strpos(mb_strtolower($products), 'персональная работа') !== false ||
                mb_strpos(mb_strtolower($products), 'питание') !== false ||
                mb_strpos(mb_strtolower($products), 'тренировки skype') !== false ||
                mb_strpos(mb_strtolower($products), 'фитнес тур') !== false ||
                mb_strpos(mb_strtolower($products), 'диагностика') !== false) {
                return '0';
            }

            if (mb_strpos(mb_strtolower($products), 'тестовая неделя') !== false) {
                return '7';
            }

            return '30';

        }
        
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
        $sheet = Sheets::spreadsheet(env('CRM_SPREADSHEET_ID_' . strtoupper($this->blogger)));
        if ($this->isKochfit || $this->isKinezio) {
            $sheet = $sheet->sheet('Sheet1');
        } else {
            $sheet = $sheet->sheet('Лист1');
        }

        $rows = $sheet->get();
        $header = $rows->pull(0);
        $values = Sheets::collection(header: $header, rows: $rows);

        $crmHistoriesCount = $this->crm_history->count();

        foreach ($values as $key => $value) {
            if ($key < $crmHistoriesCount) continue;
            
            $this->info('CRM Row ' . $key);

            $crmHistory = $this->crm_history
                ->where('email', $value['Email'])
                ->where('price', 'LIKE', $value['price'])
                ->where('sent', $value['sent'] ? Carbon::parse($value['sent'])->toDateTimeString() : null);

            $isDuplicate = $crmHistory->exists();

            try {
                
                DB::connection($this->blogger)->transaction(function () use ($crmHistory, $isDuplicate, $value, $key) {
    
                    $formName = $this->blogger === 'kochfit' ? $value['Название формы'] : $value['Form name'];
                    $currency = $this->blogger === 'kochfit' ? $value['Валюта'] : $value['Currency'];
                    $paymentStatus = $this->blogger === 'kochfit' ? $value['Статус оплаты'] : $value['Payment status'];
    
                    $this->crm_history->create([
                        'add_time' => now(),
                        'name' => $value['Name'],
                        'email' => $value['Email'],
                        'phone' => $value['Phone'],
                        'paymentsystem' => $value['paymentsystem'],
                        'orderid' => $value['orderid'],
                        'paymentid' => $value['paymentid'],
                        'products' => $value['products'],
                        'price' => $this->strToFloat($value['price']),
                        'promocode' => $value['promocode'],
                        'discount' => $this->strToFloat($value['discount']),
                        'subtotal' => $this->strToFloat($value['subtotal']),
                        'cookies' => $value['cookies'],
                        'currency' => $currency,
                        'payment_status' => $paymentStatus,
                        'referer' => $value['referer'],
                        'formid' => $value['formid'],
                        'form_name' => $formName,
                        'sent' => Carbon::parse($value['sent'])->toDateTimeString(),
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
                    ]);
                    
                    if ($this->isKinezio && mb_strpos(mb_strtolower($value['products']), 'консультация') !== false) return;
                    
                    if (!$value['Email'] || !$value['price'] || (float)$value['price'] < 100 || mb_strpos(mb_strtolower($value['products']), 'доплата') !== false) return;
    
                    if ($isDuplicate) return;
    
                    $customer = $this->customer::updateOrCreate(
                        [
                            'email' => strtolower($value['Email'])
                        ],
                        [
                            'customer_name' => $value['Name'],
                            'phone' => self::processPhone($value['Phone']),
                        ]);
    
                    $productName = $this->getProductName($value['products']);
    
                    $data = [
                        'product_name' => $productName,
                        'product_type' => self::getProductType($value['products']),
                        'product_length' => self::getProductLength($productName),
                        'product_price' => trim(explode('=', $value['products'])[1]),
                    ];
                    
                    if ($this->isKochfit) {
                        $data['product_form'] =  self::getProductForm($value['products']);
                    }
                    
                    $product = $this->product::updateOrCreate($data);
    
                    $utm = array_filter([
                        'utm_source' => $value['utm_source'],
                        'utm_medium' => $value['utm_medium'],
                        'utm_campaign' => $value['utm_campaign'],
                        'utm_term' => $value['utm_term'],
                        'utm_content' => $value['utm_content'],
                    ]);
    
                    if ($utm) {
                        $utmParam = $this->utmParam::updateOrCreate($utm);
                    }
    
                    $saleNumber = self::getSaleNumber($value['products']);
    
                    $data = [
                        'form_id' => $value['formid'],
                        'sale_number' => $saleNumber,
                        'form_name' => $formName,
                        'order_id' => $value['orderid'],
                        'payment_system' => $value['paymentsystem'],
                        'payment_id' => $value['paymentid'],
                        'subtotal' => (int)$value['subtotal'],
                        'promocode' => $value['promocode'],
                        'discount' => (int)$value['discount'],
                        'price' => (int)$value['price'],
                        'currency' => $currency,
                        'payment_status' => $paymentStatus,
                        'referer' => $value['referer'],
                        'transaction_date' => Carbon::parse($value['sent'])->toDateTimeString(),
                        'customer_id' => $customer->customer_id,
                        'product_id' => $product->product_id,
                        'utm_id' => isset($utmParam) ? $utmParam->utm_id : null,
                    ];
    
                    if ($this->isKochfit) {
                        unset($data['sale_number']);
                    }
    
                    $this->transaction::create($data);
                    
                    
                });

            } catch (\Exception $e) {
                Log::error($e->getMessage());
                $this->errors++;
                $this->error_details[] = ['строка в файле' => $key + 1, 'error' => $e->getMessage(), 'track' => $e->getTraceAsString()];
            }
            
            
        }
    }
    
    private function updateSubscriptions()
    {
        $sheet = Sheets::spreadsheet(env('SUBSCRIPTION_SPREADSHEET_ID_' . strtoupper($this->blogger)));
        $sheet = $sheet->sheet('Sheet1');

        $rows = $sheet->get();
        $header = $rows->pull(0);
        $values = Sheets::collection(header: $header, rows: $rows);
        
        $maxSubscriptionRow = $this->subscription->max('row_num') ?? 0;
        
        foreach ($values as $key => $value) {
            if ($key <= $maxSubscriptionRow) continue;
            
            $this->info('Subscription Row ' . $key);
            
            try {
                
                $table = '';
                if (mb_strpos(mb_strtolower($value['Type']), 'регулярная оплата') !== false
                    && mb_strpos(mb_strtolower($value['Url']), 'kochfit.ru') !== false
                    && mb_strpos(mb_strtolower($value['Status']), 'завершена') !== false
                ) {
                    $table = 'subscriptions';
                }

                if (mb_strpos(mb_strtolower($value['Type']), 'возврат') !== false
                    && mb_strpos(mb_strtolower($value['Url']), 'kochfit.ru') !== false
                    && mb_strpos(mb_strtolower($value['Status']), 'завершена') !== false
                ) {
                    $table = 'refunds';
                }
                
                if (!$table) continue;
                if ($value['Summ'] < 100 || !$value['Summ']) continue;
                
                $customer = $this->customer->firstOrCreate([
                    'email' => strtolower($value['E-Mail'])
                ]);

                $action_date = $value['Confirm date/time'] ? Carbon::parse($value['Confirm date/time'])->toDateTimeString() : null;
                $sum = (int)$value['Summ'];
                
                if (($table === 'subscriptions' &&
                    DB::connection($this->blogger)
                        ->table($table)
                        ->where('customer_id', $customer->customer_id)
                        ->where('subscription_date', $action_date)
                        ->where('subscription_amount', $sum)
                        ->exists())
                    || ($table === 'refunds' &&
                        DB::connection($this->blogger)
                            ->table($table)
                            ->where('customer_id', $customer->customer_id)
                            ->where('refund_date', $action_date)
                            ->where('refund_amount', $sum)
                            ->exists())
                ) {
                    continue;
                }
                
                $product = $this->product->firstOrCreate([
                    'product_name' => $value['Purpose'],
                    'product_type' => self::getProductTypeForSubscription($value['Purpose']),
                    'product_form' => self::getProductFormForSubscription($value['Purpose'], $table === 'refunds'),
                    'product_length' => self::getProductLength($value['Purpose']),
                    'product_price' => $sum,
                ]);


                $data = [
                    'customer_id' => $customer->customer_id,
                    'currency' => $value['Currency'],
                    'transaction_type' => $value['Type'],
                    'product_id' => $product->product_id,
                    'url' => $value['Url'],
                    'status' => $value['Status'],
                    'row_num' => $key
                ];
                
                
                if ($table === 'refunds') {
                    $data['refund_date'] = $action_date;
                    $data['refund_amount'] = $sum;
                } else {
                    $data['subscription_date'] = $action_date;
                    $data['subscription_amount'] = $sum;
                }
                
                
                DB::connection($this->blogger)->table($table)->insert($data);
                

            } catch (\Exception $e) {
                
                Log::error($e->getMessage());
                $this->errors++;
                $this->error_details[] = ['строка в файле' => $key + 1, 'error' => $e->getMessage()];
            }
            
            
        }
    }
    
    private function updateInstallments()
    {

        $sheet = Sheets::spreadsheet(env('INSTALLMENT_SPREADSHEET_ID_' . strtoupper($this->blogger)));
        $sheet = $sheet->sheet('Sheet1');

        $rows = $sheet->get();
        $header = $rows->pull(0);
        $values = Sheets::collection(header: $header, rows: $rows);

        $maxinstallmentRow = $this->installment->max('row_num') ?? 0;

        foreach ($values as $key => $value) {
            if ($key <= $maxinstallmentRow) continue;

            $this->info('Installments Row ' . $key);

            try {

                $customer = $this->customer->firstOrCreate([
                    'email' => strtolower($value['email'])
                ]);
                
                $amount = (int)($value['installment_amount']);
                $dateTime = $value['installment_date'] ? Carbon::parse($value['installment_date'])->toDateTimeString() : null;

                if ($this->installment
                    ->where('customer_id', $customer->customer_id)
                    ->where('installment_date', $dateTime)
                    ->where('installment_amount', $amount)
                    ->exists()) continue;

                $product = $this->product->firstOrCreate([
                    'product_name' => $value['product'],
                    'product_type' => self::getProductTypeForSubscription($value['product']),
                    'product_form' => self::getProductFormForSubscription($value['product'], true),
                    'product_length' => self::getProductLength($value['product'])
                ]);


                $data = [
                    'customer_id' => $customer->customer_id,
                    'installment_date' => $dateTime,
                    'installment_amount' => $amount,
                    'product_id' => $product->product_id,
                    'currency' => $value['currency'],
                    'row_num' => $key
                ];

                $this->installment->create($data);


            } catch (\Exception $e) {

                Log::error($e->getMessage());
                $this->errors++;
                $this->error_details[] = ['строка в файле' => $key + 1, 'error' => $e->getMessage()];
            }


        }
    }
    
    private function updateMarketing()
    {
        $sheet = Sheets::spreadsheet(env('MARKETING_SPREADSHEET_ID_' . strtoupper($this->blogger)))->sheet('Реклама');
        $rows = $sheet->get()->slice(2)->values();
        $header = $rows->pull(0);
        
        $values = Sheets::collection(header: $header, rows: $rows);
        
        $marketingHistoriesCount = $this->marketing_history->count();
        foreach ($values as $key => $value) {
            if ($key < $marketingHistoriesCount) continue;
            
            $this->info('Marketing Row ' . $key);
            
            $actualDate = $value['actual_date'] ? Carbon::parse($value['actual_date'])->toDateString() : null;
            $channel = $value['channel'];
            $landing_page = $value['landing_page'] ?? null;


            DB::connection($this->blogger)->transaction(function () use ($value, $actualDate, $channel, $landing_page) {
                
                $impressions = $this->strToInt($value['impressions']);
                $visits = $this->strToInt($value['clicks']);
                $costs = $this->strToInt($value['costs']);
                $bounces = $this->strToInt($value['bounces']);
                $conversions = $this->strToInt($value['conversions']);
                $ctr = $this->strToFloat($value['ctr']);
                $cpc = $this->strToFloat($value['cpc']);
                $cr1 = $this->strToFloat($value['cr1']);
                $conversion_cost = $this->strToFloat($value[' conversion_cost']);
                $new_followers = $this->strToInt($value[' new_followers']);
                $cr2 = $this->strToFloat($value['cr2']);
                $new_follower_cost = $this->strToFloat($value['new_follower_cost']);
                
                $data = [
                    'actual_date' => $actualDate,
                    'channel' => $channel,
                    'landing_page' => $landing_page,
                    'impressions' => $impressions,
                    'ctr' => $ctr,
                    'clicks' => $visits,
                    'costs' => $costs,
                    'bounces' => $bounces,
                    'conversions' => $conversions,
                    'conversion_cost' => $conversion_cost,
                    'new_followers' => $new_followers,
                    'new_follower_cost' => $new_follower_cost,
                    'cr2' => $cr2,
                    'cpc' => $cpc,
                    'cr1' => $cr1,
                    'add_time' => now(),
                ];
                
                if ($this->isKochfit || $this->isKinezio) {
                    unset($data['landing_page']);
                }
                $this->marketing_history->insert($data);

                $channel = $this->marketing_channel::updateOrCreate(
                    [
                        'channel_name' => $channel
                    ],
                );
                
                $arr1 = [
                    'channel_id' => $channel->channel_id,
                    'actual_date' => $actualDate,
                    'landing_page' => $landing_page,
                ];
                
                if ($this->isKochfit || $this->isKinezio) {
                    unset($arr1['landing_page']);
                }

                $this->marketing_metric::updateOrCreate($arr1, [
                    'impressions' => $impressions,
                    'clicks' => $visits,
                    'ctr' => $ctr,
                    'costs' => $costs,
                    'bounces' => $bounces,
                    'cpc' => $cpc,
                    'conversions' => $conversions,
                    'cr1' => $cr1,
                    'conversion_cost' => $conversion_cost,
                    'new_followers' => $new_followers,
                    'cr2' => $cr2,
                    'new_follower_cost' => $new_follower_cost,
                ]);
                
            });
            
        }
        
    }
    
    public function updateFollowers()
    {
        $sheet = Sheets::spreadsheet(env('MARKETING_SPREADSHEET_ID_' . strtoupper($this->blogger)))->sheet('Подписная база');
        $rows = $sheet->get()->slice(2)->values();
        $header = $rows->pull(0);

        $values = Sheets::collection(header: $header, rows: $rows);

        foreach ($values as $key => $value) {
            
            $this->info('Followers Row ' . $key);
            

            $this->follower::updateOrCreate([
                'actual_date' => $value['actual_date'] ? Carbon::parse($value['actual_date'])->toDateString() : null,
            ], [
                'add_time' => now(),
                'email' => $value['email'] === '' ? null : $this->strToInt($value['email']),
                'tg_bot' => $value['tg_bot'] === '' ? null : $this->strToInt($value['tg_bot']),
                'tg_channel' => $value['tg_channel'] === '' ? null : $this->strToInt($value['tg_channel']),
                'inst' => $value['inst'] === '' ? null : $this->strToInt($value['inst']),
                'inst_er' => $value['inst_er'] === '' ? null : $this->strToFloat($value['inst_er']),
                'vk' => $value['vk'] === '' ? null : $this->strToInt($value['vk']),
                'dzen' => $value['dzen'] === '' ? null : $this->strToInt($value['dzen']),
                'app' => $value['app'] === '' ? null : $this->strToInt($value['app']),
                'advert_tg_bot' => $value['advert_tg_bot'] === '' ? null : $this->strToInt($value['advert_tg_bot']),
            ]);

        }
        
    }    
    
    
    public function handle()
    {
        $this->blogger = $this->argument('blogger');
        $this->isKochfit = $this->blogger === 'kochfit';
        $this->isKinezio = $this->blogger === 'kinezio'; 
        
        if (!in_array($this->blogger, $this->bloggerList)) {
            $this->error('Неверный блогер');
            return;
        }
        
        $namespace = 'App\\Models\\' . ucfirst($this->blogger) . '\\';
        
        $this->updateLog = new ($namespace . 'UpdateLog')();
        $this->customer = new ($namespace . 'Customer')();
        $this->transaction = new ($namespace . 'Transaction')();
        $this->product = new ($namespace . 'Product')();
        $this->utmParam = new ($namespace . 'UtmParam')();
        $this->follower = new ($namespace . 'Follower')();
        $this->marketing_metric = new ($namespace . 'MarketingMetric')();
        $this->marketing_channel = new ($namespace . 'MarketingChannel')();
        $this->marketing_history = new ($namespace . 'MarketingHistory')();
        $this->crm_history = new ($namespace . 'CrmHistory')();
        
        if ($this->isKochfit) {
            $this->subscription = new ($namespace . 'Subscription')();
            $this->refund = new ($namespace . 'Refund')();
            $this->installment = new ($namespace . 'Installment')();
        }
        
        $updateLog = $this->updateLog::create([
            'started_at' => Carbon::now(),
            'next_start_at' => Carbon::now()->addHour()->startOfHour()
        ]);
        
        $customersCount = $this->customer->count();
        $transactionsCount = $this->transaction->count();
        $productsCount = $this->product->count();
        $utmParamsCount = $this->utmParam->count();
        $followersCount = $this->follower->count();
        $marketingMetricsCount = $this->marketing_metric->count();
        $marketingChannelsCount = $this->marketing_channel->count();
        
        if ($this->isKochfit) {
            $subscriptionsCount = $this->subscription->count();
            $installmentsCount = $this->installment->count();
            $refundsCount = $this->refund->count();
        }
        
        
        $this->updateMarketing();
        $this->updateFollowers();
        $this->updateCRM();
        if ($this->isKochfit) {
            $this->updateSubscriptions();
            $this->updateInstallments();
        }


        $customersCountNew = $this->customer->count();
        $transactionsCountNew = $this->transaction->count();
        $productsCountNew = $this->product->count();
        $utmParamsCountNew = $this->utmParam->count();
        $followersCountNew = $this->follower->count();
        $marketingMetricsCountNew = $this->marketing_metric->count();
        $marketingChannelsCountNew = $this->marketing_channel->count();

        if ($this->isKochfit) {
            $subscriptionsCountNew = $this->subscription->count();
            $installmentsCountNew = $this->installment->count();
            $refundsCountNew = $this->refund->count();
        }
        
        $data = [
            'errors' => $this->errors,
            'error_details' => $this->error_details,
            'finished_at' => Carbon::now(),
            'transactions_new_rows' => $transactionsCountNew - $transactionsCount,
            'customers_new_rows' => $customersCountNew - $customersCount,
            'products_new_rows' => $productsCountNew - $productsCount,
            'utm_params_new_rows' => $utmParamsCountNew - $utmParamsCount,
            'followers_new_rows' => $followersCountNew - $followersCount,
            'marketing_metrics_new_rows' => $marketingMetricsCountNew - $marketingMetricsCount,
            'marketing_channels_new_rows' => $marketingChannelsCountNew - $marketingChannelsCount,
        ];
        
        if ($this->isKochfit) {
            $data['subscriptions_new_rows'] = $subscriptionsCountNew - $subscriptionsCount;
            $data['installments_new_rows'] = $installmentsCountNew - $installmentsCount;
            $data['refunds_new_rows'] = $refundsCountNew - $refundsCount;
        }

        $updateLog->update($data);
        
    }
}
