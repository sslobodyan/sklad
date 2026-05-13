# mariadb-dump -u sklad -p --compact --skip-comments --add-drop-table sklad | sed -E '/\/\*![0-9]+/d; /^[[:space:]]*$/d' > dump.sql

# 1. Отримуємо список усіх таблиць, що закінчуються на _history, і готуємо прапорці ігнорування
IGNORE_FLAGS=$(mariadb -u sklad -p1qazxsw2# -N -e "SHOW TABLES FROM sklad LIKE '%_history'" | sed 's/^/--ignore-table=sklad./' | tr '\n' ' ')

# 2. Робимо ідеально чистий дамп без цих таблиць та без коментарів
mariadb-dump -u sklad -p1qazxsw2# --compact --skip-comments --add-drop-table $IGNORE_FLAGS sklad | sed -E '/\/\*![0-9]+/d; /^[[:space:]]*$/d' > dump.sql
