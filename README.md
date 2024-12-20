# Курсовой проект
Курсовой проект (КП) студента специальности 09.02.07 Информационные системы и программирование, квалификация **_разработчик веб и мультимедийных приложений_**

Тема: **_Боксерский клуб_**

Студент, группа: **_Разнобарский Александр Сергеевич, ВЕБ-21-1_**

## Структура
Обязательная структура каталогов:

```
├── docs
│   ├── Задание на курсовое проектирование.pdf (сканированный документ с подписями)
│   ├── Пояснительная записка.docx
│   ├── Пояснительная записка.pdf (формируется на защиту)
│   ├── Презентация.pdf (формируется на защиту)
│   └── Презентация.pptx (при наличии)
├── project
│    └── Исходные файлы проекта
├── README.md
└── .gitignore
```


## Запуск проекта


1. Необходимое программное обеспечение:

    - PHP 8 и выше

2. Импортировать дамп базы данных kp4_new_seed.sql

3. Изменить конфигурационный файл .env

4. Установка зависимостей npm и composer:

  ```console
\project> npm install
  ```
  ```console
\project> composer update
  ```

5. Запуск приложения:

Открываем терминал и вводим команду:
  ```console
\project> npm run dev
  ```
Затем открываем другой терминал и вводим:
  ```console
\project> php artisan serve
  ```

6. Перейти в браузере по адресу, который появится в консоли (http://127.0.0.1:8000)

