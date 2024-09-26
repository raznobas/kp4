<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Sale;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportSalesFromXlsx extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-sales-from-xlsx';

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
        // Путь к файлу XLSX
        $filePath = storage_path('app/sales_data.xlsx');

        // Загрузка файла
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Получение данных из файла
        $data = $worksheet->toArray();

        // Получение заголовков
        $headers = array_shift($data);

        // Сопоставление заголовков с полями
        $headerMap = [
            'ФИО клиента' => 'full_name',
            'Дата оплаты' => 'payment_date',
            'Спорт' => 'sport',
            'Тип абонемента' => 'subscription_type',
            'Тренер' => 'trainer',
            // Другие поля
        ];

        // Обработка данных
        foreach ($data as $row) {
            $rowData = array_combine($headers, $row);

            // Разделение ФИО на отдельные поля
            $fullName = $rowData[$headerMap['ФИО клиента']];
            $nameParts = explode(' ', $fullName);

            // Проверка, что ФИО состоит из двух или более слов
            if (count($nameParts) < 2) {
                $this->warn("Игнорируем строку с ФИО: $fullName");
                continue; // Пропускаем эту строку
            }

            $surname = $nameParts[0] ?? '';
            $name = $nameParts[1] ?? '';
            $patronymic = $nameParts[2] ?? '';

            // Создание нового клиента
            $client = Client::create([
                'surname' => $surname,
                'name' => $name,
                'patronymic' => $patronymic,
                // Другие поля клиента
            ]);

            // Создание записи в таблице sales
            $saleData = [];
            foreach ($headerMap as $header => $field) {
                if ($field === 'full_name') {
                    continue; // Пропускаем ФИО, так как оно уже обработано
                }
                $saleData[$field] = $rowData[$header];
            }
            $saleData['client_id'] = $client->id;

            Sale::create($saleData);
        }

        $this->info('Данные успешно импортированы!');

        return 0;
    }
}
