# Studentų Paraiškų Valdymo Sistema (PHP + SQLite)

Ši sistema yra pilnai funkcionuojanti studentų paraiškų valdymo aplikacija, sukurta naudojant **gryną PHP**, **SQLite**, **Repository + Service architektūrą**, **View šablonus**, dalinį **routerį**, ir įdiegtas modernias **saugumo priemones** (CSRF, XSS apsauga, login rate limiting, SQL injection prevencija).

---

## 📌 1. Funkcionalumas

### 👩‍🎓 Studentas gali:

- Registruotis sistemoje
- Prisijungti su el. paštu
- Kurti paraiškas (ruošinius)
- Redaguoti ruošinius
- Pateikti paraiškas („Pateikta“)
- Negalima pateikti daugiau nei **3 paraiškų vieno tipo**
- Matyti administratoriaus atmetimo komentarus

### 🧑‍💼 Administratorius gali:

- Prisijungti
- Matyti visas paraiškas
- Patvirtinti paraiškas
- Atmesti paraiškas su komentaru

---

## 🏗️ 2. Architektūra

Projektas naudoja aiškią sluoksninę architektūrą:

```
public/ (endpoint'ai)
    → Controller (ApplicationController)
        → Service (ApplicationService)
            → Repository (ApplicationRepository)
                → DB (SQLite per PDO)
views/
    → HTML šablonai
```

### Controller

- Apdoroja HTTP užklausas
- Kviesčia Service metodus
- Renka duomenis View šablonams

### Service

- Verslo taisyklės:
  - „max 3 submitted per tipo“
  - statusų keitimas
  - atmetimo logika
  - rate limiting ruošinių kūrimui
- Nežino apie HTML ar SQL

### Repository

- Visi SQL užklausų metodai vienoje vietoje
- PDO `prepare` apsauga nuo SQL injection

### View

- HTML šablonai be logikos
- Naudojamas `htmlspecialchars` (XSS apsauga)

---

## 🪄 3. SOLID principai

### ✔ SRP

Kiekvienas sluoksnis turi vieną atsakomybę (Controller, Service, Repository, View).

### ✔ DIP

Controller → Service → Repository → PDO priklausomybės tiekiamos per konstruktorių.

### ✔ OCP/LSP

Verslo logika iškelta į Service, todėl keitimai nedaro įtakos kitiems sluoksniams.

---

## 🧩 4. Naudoti Design Pattern'ai

### Repository Pattern

Failas: `src/ApplicationRepository.php`

- SQL atsieta nuo logikos
- Lengvai testuojama
- Galima pakeisti DB

### Service Layer Pattern

Failas: `src/ApplicationService.php`

- Visi verslo sprendimai vienoje vietoje
- Testuojama izoliuotai
- Controlleris išlieka „plonas“

### Partial Router Pattern

Failas: `public/index.php`

- `/login`, `/register` nukreipiami per routerį
- `/applications` dalis kol kas palikta su klasikiniu entrypoint

---

## 🔐 5. Saugumo sprendimai

### ✔ SQL Injection apsauga

- Naudojami tik ruošiami statement'ai (`prepare` + `execute`)
- `PDO::ATTR_EMULATE_PREPARES = false`

### ✔ XSS apsauga

- Absoliučiai visi HTML dinaminiai laukeliai pereina per `htmlspecialchars`

### ✔ CSRF apsauga

Failas: `src/csrf.php`

- Kiekviena POST forma turi `csrf_token`
- Serveris tikrina žetoną prieš apdorojimą

### ✔ Login Rate Limiting

- Po 5 nesėkmingų bandymų – blokavimas 5 minutėms

### ✔ Session Hardening

- `session_regenerate_id(true)` po sėkmingo prisijungimo

### ✔ Spam apsauga

- Studentas negali sukurti daugiau nei 5 ruošinių per 60 sekundžių

---

## 🧪 6. Unit testai (PHPUnit)

Testai tikrina:

- Ruošinio kūrimo validaciją
- „max 3 submitted“ taisyklę
- Ruošinio pateikimą
- Paraiškos tvirtinimą
- Atmetimą su komentaru
- Spam aptikimą

### Kaip paleisti testus:

```
composer install
vendor/bin/phpunit
```

Tikėtinas rezultatas:

```
OK (8 tests, 20+ assertions)
```

---

## 📁 7. Projekto struktūra

```
students-app/
│
├── public/
│   ├── index.php
│   ├── login.php        (legacy)
│   ├── register.php     (legacy)
│   ├── applications/
│   │   ├── index.php
│   │   ├── edit.php
│   │   └── reject.php
│   └── css/
│       └── water.css
│
├── src/
│   ├── db.php
│   ├── View.php
│   ├── csrf.php
│   ├── ApplicationRepository.php
│   ├── ApplicationService.php
│   ├── ApplicationController.php
│
├── views/
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   └── applications/
│       ├── list.php
│       ├── edit.php
│       └── reject.php
│
├── tests/
│   ├── ApplicationServiceTest.php
│   └── bootstrap.php
│
├── data/
│   └── app.sqlite
│
├── composer.json
├── phpunit.xml
└── README.md
```

---

## ▶️ 8. Paleidimas lokaliai

### 1. Įdiegti priklausomybes

```
composer install
```

### 2. Paleisti serverį

```
php -S localhost:8000 -t public
```

### 3. Atidaryti naršyklėje

```
http://localhost:8000/
```

### 4. Numatyti vartotojai:

| Rolė      | El. paštas          | Slaptažodis |
| --------- | ------------------- | ----------- |
| Studentas | student@example.com | student123  |
| Adminas   | admin@example.com   | admin123    |

---

## 🚀 9. Ką būtų galima patobulinti ateityje

- Pilnas Router (front controller architektūra)
- PSR-4 autoloading (Composer autoload)
- State Pattern paraiškų būsenoms
- REST API / JSON endpoint'ai
- Docker konteineriai
- Integraciniai testai UI daliai
- Modernus UI (Bootstrap/Tailwind)

---

## 👤 10. Autorius

- **Povilas Urbonas**
- **El. paštas**
- **GitHub profilis https://github.com/PovilasU**

---
