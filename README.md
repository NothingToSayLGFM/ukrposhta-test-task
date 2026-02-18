# Ukrposhta Test Project

## Запуск проєкту

У корені проєкту виконати:

```bash
docker compose up -d --build
```

Почекати поки зберуться та запустяться контейнери.

Перевірити що все працює:

```bash
docker ps
```

## Запуск frontend

Перейти в папку frontend:

```bash
cd frontend
```

Використати версію Node.js з файлу .nvmrc:

```bash
nvm use
```

Якщо ця версія ще не встановлена:

```bash
nvm install
nvm use
```

Встановити залежності:

```bash
npm install
```

Запустити dev сервер на 5173 порту:

```bash
npm run dev -- --port 5173
```

Frontend буде доступний на http://localhost:5173

## Імпорт даних у базу

Імпорт запускається з кореня проєкту командою:

```bash
docker exec -it ukrposhta_php php cli/import.php /var/www/storage/imports/postindex.zip
```

## Файл для імпорту

Можна використовувати будь-який zip архів якщо:

* всередині є csv файл
* csv має потрібні поля

Щоб використати інший файл достатньо покласти його в:

```
storage/imports/
```

і передати новий шлях у команді імпорту.

## Підключення до бази

База доступна на:

```
127.0.0.1:3307
```

Сама таблиця створиться автоматично під назваою post_indexes 

Контейнер з базою: ukrposhta_db

## Повний порядок запуску

```bash
docker compose up -d --build

cd frontend
nvm use
npm install
npm run dev -- --port 5173

docker exec -it ukrposhta_php php cli/import.php /var/www/storage/imports/postindex.zip
```

Swagger документація знаходиться по шляху 

```bash
http://localhost:8080/swagger.php
```