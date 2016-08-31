# Консольный парсер - описание работы
## Серверная часть
Настройки доступа к базе данных в файле db_config.php

Первоначальный запуск следует сделать с ключем <i>-s</i>, парсер создаст нужную таблицу

    ```
    php go.php -s=1
    ```

Для запуска парсера сайта воспользуйтесь командой

    ```
    php go.php -d=tut.by -p=http://
    ```
