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
    private $dublicates = 0;
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
    private Model $paypal;
    private Model $tocard;
    private Model $crm_history;
    private string $blogger;
    private bool $isKochfit;
    private bool $isKinezio;
    private bool $isPopovich;


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
        
        return 'Покупка';
        
   }
    
    private function getEmail($email): string
    {
        $email = strtolower($email);
        
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        // Remove double dots
        $email = preg_replace('/\.\.+/', '.', $email);

        // Trim spaces
        $email = trim($email);
        
        return $email;
    }
    
    
    private function getProductType($products): string
    {
    
        if ($this->isKinezio) {
            if (mb_stripos($products, 'фундамент') !== false
                && (mb_stripos($products, 'с обратной связью') !== false || mb_stripos($products, 'премиум') !== false)) {
                return 'Фундамент Премиум';
            }
            
            if (mb_stripos($products, 'фундамент') !== false
                && (mb_stripos($products, 'без обратной связи') !== false || mb_stripos($products, 'базовый') !== false)) {
                return 'Фундамент Базовый';
            }
            
            if (mb_stripos($products, 'фундамент') !== false && mb_strpos(mb_strtolower($products), '2.0') !== false) {
                return 'ФД 2.0';
            }
            
            if (mb_stripos($products, 'анатомия') !== false) {
                return 'Анатомия'; 
            }
            
            if (mb_stripos($products, 'дыхание') !== false) {
                return 'Дыхание'; 
            }
            
            if (mb_stripos($products, 'пробный') !== false) {
                return 'Пробный';
            }
            
            return 'Другое';
        } else if ($this->isKochfit) {

            if (mb_strpos(mb_strtolower($products), 'лайт') !== false || mb_strpos(mb_strtolower($products), 'тандарт') !== false) {
                return 'Красота и здоровье Стандарт';
            }

            if (mb_strpos(mb_strtolower($products), 'премиум') !== false) {
                return 'Красота и здоровье Премиум';
            }
            
            if (mb_strpos(mb_strtolower($products), 'тестовая') !== false) {
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
            
            if (mb_stripos($products, 'зарядки') !== false) {
                return 'Зарядки';
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
        } else if ($this->isKinezio) {
            $separator = mb_strpos($products, '/') !== false ? '/' : '- 1';
        }
        else {
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
    
    private function getProductMeasure($products): string
    {
        
        if ($this->isPopovich) {
            if (mb_strpos(mb_strtolower($products), 'введение') !== false) {
                return 'day';
            }
            
            return 'week';

        }
        
        if (mb_strpos(mb_strtolower($products), 'тестовая неделя') !== false) {
            return 'day';
        }
        
        return 'month';
    }
    

    private function getProductLength($products): string
    {
        if ($this->isKinezio) {
            if (mb_stripos($products, 'фундамент') !== false && mb_stripos($products, 'модуль') !== false) {
                return '2';
            }
            
            if (mb_stripos($products, 'фундамент') !== false) {
                return '6';
            }

            if (mb_stripos($products, 'анатомия') !== false || mb_stripos($products, 'дыхание') !== false) {
                return '1';
            }
            
            return '0';
        }
        
        if ($this->isKochfit) {
            
            $numbers = preg_replace('/[^0-9]/', '', $products);
            
            if ($numbers) return $numbers;

            if (mb_strpos(mb_strtolower($products), 'годовой') !== false) {
                return '12';
            }

            if (mb_strpos(mb_strtolower($products), 'ведение тренировки') !== false ||
                mb_strpos(mb_strtolower($products), 'персональная работа') !== false ||
                mb_strpos(mb_strtolower($products), 'питание') !== false ||
                mb_strpos(mb_strtolower($products), 'skype') !== false ||
                mb_strpos(mb_strtolower($products), 'фитнес тур') !== false ||
                mb_strpos(mb_strtolower($products), 'семинар') !== false ||
                mb_strpos(mb_strtolower($products), 'диагностика') !== false) {
                return '0';
            }

            if (mb_strpos(mb_strtolower($products), 'тестовая неделя') !== false) {
                return '7';
            }

            return '1';

        }
        
        if ($this->isPopovich) {
            if (mb_strpos(mb_strtolower($products), '12') !== false) {
                return '60';
            } 
            
            if (mb_strpos(mb_strtolower($products), '1') !== false) {
                return '5';
            }
            if (mb_strpos(mb_strtolower($products), '3') !== false) {
                return '15';
            }

            if (mb_strpos(mb_strtolower($products), '6') !== false) {
                return '30';
            }

            if (mb_strpos(mb_strtolower($products), 'введение') !== false) {
                return '14';
            }
            
            if (mb_strpos(mb_strtolower($products), 'годовой') !== false) {
                return '60';
            }
             
            if (mb_strpos(mb_strtolower($products), 'архив силовой') !== false) {
                return '5';
            }
            
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

            $email = $this->getEmail($value['Email']);
            $crmHistory = $this->crm_history
                ->where('email', $email)
                ->where('price', 'LIKE', $value['price'])
                ->where('sent', $value['sent'] ? Carbon::parse($value['sent'])->toDateTimeString() : null);

            $isDuplicate = $crmHistory->exists();

            try {
                
                DB::connection($this->blogger)->transaction(function () use ($crmHistory, $isDuplicate, $value, $key, $email) {
    
                    $formName = $this->blogger === 'kochfit' ? $value['Название формы'] : $value['Form name'];
                    $currency = $this->blogger === 'kochfit' ? $value['Валюта'] : $value['Currency'];
                    $paymentStatus = $this->blogger === 'kochfit' ? $value['Статус оплаты'] : $value['Payment status'];
    
                    $this->crm_history->create([
                        'add_time' => now(),
                        'name' => $value['Name'],
                        'email' => $email,
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
                    
                    if (mb_stripos($value['Name'], 'test') !== false || mb_stripos($value['Name'], 'тест') !== false) return;
                    if ($this->isKinezio && self::getProductType($value['products']) === 'Другое') return;
                    if (!$email || !$value['price'] || (float)$value['price'] <= 100 || mb_stripos($value['products'], 'доплата') !== false) return;
                    if ($isDuplicate) return;
    
                    $customer = $this->customer::updateOrCreate(
                        [
                            'email' => $email
                        ],
                        [
                            'customer_name' => $value['Name'],
                            'phone' => self::processPhone($value['Phone']),
                        ]);

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
                    
                    $productName = $this->getProductName($value['products']);
                    
                    $discount = $this->strToFloat($value['discount']);
    
                    $data = [
                        'product_name' => $productName,
                        'product_type' => self::getProductType($value['products']),
                        'product_length' => self::getProductLength($productName),
                        'product_price' => trim(explode('=', $value['products'])[1]),
                        'length_measure' => self::getProductMeasure($value['products'])
                    ];
                    
                    if ($this->isKochfit) {
                        $data['product_form'] =  self::getProductForm($value['products']);
                    }

                    $transactionData = [
                        'form_id' => $value['formid'],
                        'sale_number' => $saleNumber,
                        'form_name' => $formName,
                        'order_id' => $value['orderid'],
                        'payment_system' => $value['paymentsystem'],
                        'payment_id' => $value['paymentid'],
                        'subtotal' => $this->strToFloat($value['subtotal']),
                        'promocode' => $value['promocode'],
                        'discount' => $discount,
                        'currency' => $currency,
                        'payment_status' => $paymentStatus,
                        'referer' => $value['referer'],
                        'transaction_date' => Carbon::parse($value['sent'])->toDateTimeString(),
                        'customer_id' => $customer->customer_id,
                        'utm_id' => isset($utmParam) ? $utmParam->utm_id : null,
                    ];

                    $products = $this->product->getProducts($data);
                    $amount = $this->strToFloat($value['price']);
                    foreach ($products as $product) {
                        if ($product->product_name === 'Онлайн-курс Анатомия движения') {
                            $amount = 7000;
                        }

                        $transactionData['price'] = count($products) > 1 ? ($product->product_price - $discount / 2) : $amount;
                        $transactionData['product_id'] = $product->product_id;
                        
                        $action_date = $transactionData['transaction_date'];
                        
                        if ($this->isKochfit && $dublicate = $this->subscription
                                ->leftJoin('products', 'subscriptions.product_id', 'products.product_id')
                                ->where('products.product_type', $product->product_type)
                                ->where('products.product_length', $product->product_length)
                                ->where('products.length_measure', $product->length_measure)
                                ->where('customer_id', $customer->customer_id)
                                ->whereBetween('subscription_date', [Carbon::parse($action_date)->subHour()->toDateTimeString(), Carbon::parse($action_date)->addHour()->toDateTimeString()])
                                ->where('subscription_amount', $transactionData['price'])
                                ->first()
                        ) {
                            $this->dublicates++;

                            Log::info('Subscription already exists', [
                                'subscription_id' => $dublicate->id,
                                'product_type' => $product->product_type,
                                'product_length' => $product->product_length,
                                'length_measure' => $product->length_measure,
                                'price' => $transactionData['price'],
                                'action_date' => $action_date,
                                'customer_id' => $customer->customer_id,
                                'строка в файле crm' => $key + 1
                            ]);
                            
                            continue;
                        }
                        
                        
                        
                        if (count($products) > 1) {
                            if ($value['promocode']) {
                                $transactionData['subtotal'] = $product->product_price;
                                $transactionData['discount'] = $discount / 2;
                            } else {
                                $transactionData['subtotal'] = null;
                                $transactionData['discount'] = null;
                            }
                            
                        }
                        
                        $this->transaction::create($transactionData);
                    }
                    
                });

            } catch (\Exception $e) {
                Log::error($e->getMessage());
                $this->errors++;
                $this->error_details[] = ['строка в файле crm' => $key + 1, 'error' => $e->getMessage(), 'track' => $e->getTraceAsString()];
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
                if (mb_strpos(mb_strtolower($value['Type']), 'оплата') !== false
                    && mb_strpos(mb_strtolower($value['Url']), 'kochfit.ru') !== false
                    && mb_strpos(mb_strtolower($value['Status']), 'completed') !== false
                ) {
                    $table = 'subscriptions';
                }

                if (mb_strpos(mb_strtolower($value['Type']), 'возврат') !== false
                    && mb_strpos(mb_strtolower($value['Url']), 'kochfit.ru') !== false
                    && mb_strpos(mb_strtolower($value['Status']), 'completed') !== false
                ) {
                    $table = 'refunds';
                }
                
                if (!$table) continue;
                
                $sum = abs(self::strToFloat($value['Summ']));

                if ( !$value['Summ'] || $sum < 100) continue;
                
                $email = $this->getEmail($value['Customer']);

                $customer = $this->customer->firstOrCreate([
                    'email' => $email
                ]);

                $action_date = $value['Confirm date/time'] ? Carbon::parse($value['Confirm date/time'])->toDateTimeString() : null;
                
                $data = [
                    'product_type' => $this->getProductType($value['Purpose']),
                    'product_form' => $this->getProductForm($value['Purpose']),
                    'product_length' => $this->getProductLength($value['Purpose']),
                    'length_measure' => $this->getProductMeasure($value['Purpose']),
                    'product_name' => $value['Purpose'],
                    'product_price' => $sum
                ];

                $products = $this->product->getProducts($data);

                foreach ($products as $product) {
                    $sum = count($products) > 1 ? $product->product_price : $sum;
                    
                    if ($table === 'subscriptions' && $dublicate = $this->transaction
                            ->leftJoin('products', 'transactions.product_id', 'products.product_id')
                            ->where('products.product_type', $product->product_type)
                            ->where('products.product_length', $product->product_length)
                            ->where('products.length_measure', $product->length_measure)
                            ->where('customer_id', $customer->customer_id)
                            ->whereBetween('transaction_date', [Carbon::parse($action_date)->subHour()->toDateTimeString(), Carbon::parse($action_date)->addHour()->toDateTimeString()])
                            ->where('price', $sum)
                            ->first()
                    ) {
                        
                        $this->dublicates++;
                        
                        Log::info('Transaction already exists', [
                            'transaction_id' => $dublicate->transaction_id,
                            'product_type' => $product->product_type,
                            'product_length' => $product->product_length,
                            'length_measure' => $product->length_measure,
                            'price' => $sum,
                            'action_date' => $action_date,
                            'customer_id' => $customer->customer_id,
                            'строка в файле подписки' => $key + 1
                        ]);
                        continue;
                    }

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


                    $transactionData = [
                        'customer_id' => $customer->customer_id,
                        'currency' => $value['Currency'],
                        'transaction_type' => $value['Type'],
                        'product_id' => $product->product_id,
                        'url' => $value['Url'],
                        'status' => $value['Status'],
                        'row_num' => $key
                    ];

                    if ($table === 'refunds') {
                        $transactionData['refund_date'] = $action_date;
                        $transactionData['refund_amount'] = $sum;
                    } else {
                        $transactionData['subscription_date'] = $action_date;
                        $transactionData['subscription_amount'] = $sum;
                    }

                    DB::connection($this->blogger)->table($table)->insert($transactionData);

                }
                
                

            } catch (\Exception $e) {
                
                Log::error($e->getMessage());
                $this->errors++;
                $this->error_details[] = ['строка в файле подписки' => $key + 1, 'error' => $e->getMessage()];
            }
            
            
        }
    }
    
    private function updateInstallments()
    {

        $sheet = Sheets::spreadsheet(env('INSTALLMENT_SPREADSHEET_ID_' . strtoupper($this->blogger)));
        $sheet = $sheet->sheet('installments');

        $this->updatePayments('installment', $sheet);
    }
    
    private function updatePayments($type, $sheet)
    {
        $rows = $sheet->get();
        $header = $rows->pull(0);
        $values = Sheets::collection(header: $header, rows: $rows);

        $maxRow = $this->$type->max('row_num') ?? 0;

        foreach ($values as $key => $value) {
            if ($key <= $maxRow) continue;

            $this->info($type . ' Row ' . $key);

            try {

                $email = $this->getEmail($value['email']);

                $customer = $this->customer::firstOrCreate(
                    [
                        'email' => $email
                    ],
                    [
                        'customer_name' => $value['name'] ?? null,
                        'phone' => isset($value['phone']) ? self::processPhone($value['phone']) : null,
                    ]);

                $amount = self::strToFloat(($value[$type . '_amount']));
                $dateTime = $value[$type . '_date'] ? Carbon::parse($value[$type . '_date'])->toDateTimeString() : null;

                $data = [
                    'product_type' => $this->getProductType($value['product']),
                    'product_form' => $this->getProductForm($value['product']),
                    'product_length' => $this->getProductLength($value['product']),
                    'length_measure' => $this->getProductMeasure($value['product']),
                    'product_price' => $value[$type . '_amount'],
                    'product_name' => $value['product']
                ];

                $products = $this->product->getProducts($data);

                foreach ($products as $product) {

                    $amount = count($products) > 1 ? $product->product_price : $amount;
                    
                    if ($this->$type
                        ->where('customer_id', $customer->customer_id)
                        ->where($type . '_date', $dateTime)
                        ->where($type . '_amount', $amount)
                        ->exists()) continue;
                    
                    
                    $data = [
                        'customer_id' => $customer->customer_id,
                        $type . '_date' => $dateTime,
                        $type . '_amount' => $amount,
                        'product_id' => $product->product_id,
                        'currency' => $value['currency'],
                        'row_num' => $key
                    ];

                    $this->$type->create($data);
                }


            } catch (\Exception $e) {

//                dd($e->getMessage());
                Log::error($e->getMessage());
                $this->errors++;
                $this->error_details[] = ['строка в файле payments' => $key + 1, 'error' => $e->getMessage()];
            }


        }
    }
    
    private function updatePaypal()
    {
        $sheet = Sheets::spreadsheet(env('INSTALLMENT_SPREADSHEET_ID_' . strtoupper($this->blogger)));
        $sheet = $sheet->sheet('paypal');
        
        $this->updatePayments('paypal', $sheet);
    }
    
    private function updateTocard()
    {
        $sheet = Sheets::spreadsheet(env('INSTALLMENT_SPREADSHEET_ID_' . strtoupper($this->blogger)));
        $sheet = $sheet->sheet('tocard');
        
        $this->updatePayments('tocard', $sheet);
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
                'youtube' => $value['youtube'] === '' ? null : $this->strToInt($value['youtube']),
                'app' => $value['app'] === '' ? null : $this->strToInt($value['app']),
                'advert_tg_bot' => $value['advert_tg_bot'] === '' ? null : $this->strToInt($value['advert_tg_bot']),
                ...($this->isPopovich ? ['yandex_music' => $value['yandex_music'] === '' ? null : $this->strToInt($value['yandex_music'])] : [])
            ]);

        }
        
    }    
    
    
    public function handle()
    {
        $this->blogger = $this->argument('blogger');
        $this->isKochfit = $this->blogger === 'kochfit';
        $this->isKinezio = $this->blogger === 'kinezio'; 
        $this->isPopovich = $this->blogger === 'popovich'; 
        
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
            $this->paypal = new ($namespace . 'Paypal')();
            $this->tocard = new ($namespace . 'Tocard')();
        }
        
        $updateLog = $this->updateLog::create([
            'started_at' => Carbon::now(),
            'next_start_at' => Carbon::now()->addMinutes(5)
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
            $paypalCount = $this->paypal->count();
            $tocardCount = $this->tocard->count();
        }


        $this->updateCRM(); // должно идти первым
        
        $this->updateMarketing();
        $this->updateFollowers();
        if ($this->isKochfit) {
            $this->updateSubscriptions();
            $this->updateInstallments();
            $this->updatePaypal();
            $this->updateTocard();
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
            $paypalCountNew = $this->paypal->count();
            $tocardCountNew = $this->tocard->count();
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
            $data['duplicates'] = $this->dublicates;
            $data['subscriptions_new_rows'] = $subscriptionsCountNew - $subscriptionsCount;
            $data['installments_new_rows'] = $installmentsCountNew - $installmentsCount;
            $data['refunds_new_rows'] = $refundsCountNew - $refundsCount;
            $data['paypal_new_rows'] = $paypalCountNew - $paypalCount;
            $data['tocard_new_rows'] = $tocardCountNew - $tocardCount;
        }

        $updateLog->update($data);
        
    }
}
