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

- HTML šablonai be „verslo logikos“
- Naudojamas `htmlspecialchars` (XSS apsauga)

---

## 🧩 3. Naudoti Design Pattern'ai

### Repository Pattern

Failas: `src/ApplicationRepository.php`

- SQL atsieta nuo verslo logikos
- Lengvai testuojama
- Galima pakeisti DB (SQLite → MySQL ir t. t.)

### Service Layer Pattern

Failas: `src/ApplicationService.php`

- Visi verslo sprendimai vienoje vietoje
- Testuojama izoliuotai su in-memory SQLite
- Controlleris išlieka „plonas“

### Partial Router Pattern

Failas: `public/index.php`

- `/login` ir `/register` nukreipiami per paprastą Router klasę
- Paraiškų dalis (`/applications/index.php`) šiuo metu realizuota klasikiniu entrypoint, bet lengvai pritaikoma routeriui ateityje

---

## 🔐 4. Saugumo sprendimai

### ✔ SQL Injection apsauga

- Naudojami tik ruošiami statement'ai (`prepare` + `execute`)
- `PDO::ATTR_EMULATE_PREPARES = false`
- Visi kintamieji paduodami kaip parametrai (`:id`, `:student_id`, `:email`, ...)

### ✔ XSS apsauga

- Absoliučiai visi HTML dinaminiai laukeliai pereina per:
  ```php
  htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
  ```

### ✔ CSRF apsauga

Failas: `src/csrf.php`

- Kiekviena POST forma turi `csrf_token`:
  ```html
  <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>" />
  ```
- Serveris tikrina žetoną prieš apdorojant duomenis:
  ```php
  if (!csrf_verify($_POST['csrf_token'] ?? null)) { ... }
  ```

### ✔ Login Rate Limiting

- Po 5 nesėkmingų bandymų login blokavimo langas (pvz. 5 minutės)
- Sumažina bruteforce riziką

### ✔ Session Hardening

- `session_regenerate_id(true)` po sėkmingo prisijungimo
- Sumažina session fixation riziką

### ✔ Spam apsauga (ruošinių kūrimui)

- Studentas negali sukurti daugiau nei 5 ruošinių per 60 sekundžių
- Apskaita daroma `ApplicationRepository::countRecentDraftsForStudent(...)` ir tikrinama `ApplicationService::createDraftForStudent(...)`

---

## 🧪 5. Unit testai (PHPUnit)

Testai tikrina:

- Ruošinio kūrimo validaciją
- „max 3 submitted“ taisyklę vienam tipui
- Ruošinio pateikimą (`draft → submitted`)
- Patvirtinimą (`submitted → approved`)
- Atmetimą su komentaru (`submitted → rejected` + įrašomas komentaras)
- Atmetimo klaidą, jei komentaras tuščias
- Spam aptikimą (rate limiting ruošinių kūrimui)
- Ignoravimą, kai bandoma patvirtinti ne „submitted“ paraišką

### Kaip paleisti testus:

```bash
composer install
vendor/bin/phpunit
```

Tikėtinas rezultatas, pvz.:

```text
OK (10 tests, 31 assertions)
```

---

## 🌐 6. Paprastas REST API sluoksnis (`/api/...`)

Projektas turi minimalų REST API sluoksnį paraiškų darbo demonstravimui.

### Failas: `public/api/applications.php`

Pagrindiniai endpoint'ai:

#### 1) Gauti paraiškų sąrašą (studentui – jo, adminui – visas)

**Request:**

```http
GET /api/applications.php
Cookie: PHPSESSID=...
```

**Atsakymas (200 OK, JSON):**

```json
[
  {
    "id": 1,
    "student_id": 2,
    "title": "Test paraiška",
    "description": "Aprašymas",
    "type": "Stipendija",
    "status": "submitted",
    "rejection_comment": null,
    "created_at": "2025-11-28 12:00:00"
  }
]
```

#### 2) Sukurti naują ruošinį (studento API)

**Request:**

```http
POST /api/applications.php
Content-Type: application/json
Cookie: PHPSESSID=...

{
  "title": "Nauja paraiška",
  "description": "Aprašymas",
  "type": "Stipendija"
}
```

**Atsakymas (201 Created):**

```json
{
  "success": true,
  "message": "Paraiškos ruošinys sukurtas sėkmingai."
}
```

#### 3) Klaidos pavyzdys

Jei viršytas ruošinių rate limit:

```json
{
  "success": false,
  "error": "Per daug bandymų sukurti paraiškas. Palaukite minutę ir bandykite vėl."
}
```

> API sluoksnis naudoja tą patį `ApplicationRepository` ir `ApplicationService`, todėl verslo logika nesidubliuoja, tik pasikeičia atvaizdavimo forma (HTML → JSON).

---

## 📁 7. Projekto struktūra

```text
students-app/
│
├── public/
│   ├── index.php           # dalinis routeris (/login, /register)
│   ├── login.php           # legacy login įėjimo taškas
│   ├── register.php        # legacy registracija
│   ├── logout.php
│   ├── api/
│   │   └── applications.php  # paprastas REST API
│   ├── applications/
│   │   ├── index.php       # pagrindinis paraiškų HTML endpoint'as
│   │   ├── edit.php
│   │   └── reject.php
│   └── css/
│       └── water.css
│
├── src/
│   ├── db.php
│   ├── View.php
│   ├── csrf.php
│   ├── Router.php          # paprastas Router login/registracijai
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

```bash
composer install
```

### 2. Paleisti serverį

```bash
php -S localhost:8000 -t public
```

### 3. Atidaryti naršyklėje

```text
http://localhost:8000/
```

### 4. Numatyti vartotojai:

| Rolė      | El. paštas          | Slaptažodis |
| --------- | ------------------- | ----------- |
| Studentas | student@example.com | student123  |
| Adminas   | admin@example.com   | admin123    |

---

## 🚀 9. Ką daryčiau kitaip, jei turėčiau daugiau laiko

- Pilnas Router (front controller architektūra) visiems route'ams (`/applications` ir pan.)
- PSR-4 autoloading (Composer autoload vietoje `require`)
- State Pattern paraiškų būsenoms (`draft/submitted/approved/rejected` kaip atskiri objektai)
- Pilnai išbaigtas REST API (`/api/login`, `/api/applications/{id}`, ir t. t.)
- Docker konteinerizacija (PHP + SQLite + web serveris vienam komplekte)
- Papildomi integraciniai testai UI ir API sluoksniui
- Modernus UI (Bootstrap/Tailwind) – nors pagal užduotį dizainas nėra vertinamas

---

## 👤 10. Autorius

Įrašykite savo duomenis:

- **Povilas Urbonas**
- **El. paštas**
- **GitHub profilis https://github.com/PovilasU**
