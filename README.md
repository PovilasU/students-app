# Studentų Paraiškų Valdymo Sistema (PHP + SQLite)

Ši sistema yra pilnai funkcionuojanti studentų paraiškų valdymo aplikacija, sukurta naudojant **gryną PHP**, **SQLite**, aiškią **Controller → Service → Repository → View** architektūrą, paprastą **REST API sluoksnį** (`/api/...`) ir įdiegtas **saugumo priemones** (CSRF, XSS, login rate limiting, SQL injection prevencija).

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

## 🏗️ 2. Architektūra (sluoksniai)

Projekto backend dalis suskaidyta į aiškius sluoksnius:

```text
public/ (entrypoint'ai ir API)
   ├─ applications/*.php   (HTML endpoint'ai)
   ├─ api/*.php            (REST API endpoint'ai)
   └─ index.php            (paprastas router'is /login, /register)

Controller (ApplicationController)
   ↓
Service (ApplicationService)
   ↓
Repository (ApplicationRepository)
   ↓
DB (SQLite per PDO, db.php)

View (views/*.php) – HTML šablonai
```

### Controller

Failas: `src/ApplicationController.php`

- priima duomenis iš `public/applications/*.php`,
- kviečia `ApplicationService`,
- paruošia duomenis `View` šablonams (`views/applications/*.php`).

### Service

Failas: `src/ApplicationService.php`

- verslo taisyklės:
  - „max 3 submitted per tipo“,
  - statusų keitimas `draft → submitted → approved / rejected`,
  - atmetimo komentaro validacija,
  - „draft“ redagavimo teisės,
  - paprastas rate limiting ruošinių kūrimui;
- nežino apie HTML ar HTTP – todėl lengvai testuojamas.

### Repository

Failas: `src/ApplicationRepository.php`

- kapsuliuoja SQL užklausas:
  - `findAllForStudent`, `findAll`, `findById`,
  - `countSubmittedByTypeForStudent`,
  - `insertDraft`, `updateDraft`,
  - `updateStatus`, `updateStatusAndComment`,
  - `countRecentDraftsForStudent`;
- naudoja `PDO::prepare` / `execute` (apsauga nuo SQL injection).

### View

Katalogai:

- `views/auth/*.php` – login / registracija,
- `views/applications/*.php` – paraiškų sąrašas, redagavimas, atmetimas.

Visas HTML atskirtas nuo verslo logikos.

---

## 🪄 3. SOLID principai

### SRP (Single Responsibility Principle)

- Controller – tik request → service → view srautas,
- Service – tik verslo taisyklės,
- Repository – tik SQL prieiga,
- View – tik HTML atvaizdavimas.

### DIP (Dependency Inversion Principle)

- `ApplicationController` gauna `ApplicationService` per konstruktorių,
- `ApplicationService` gauna `ApplicationRepository`,
- `ApplicationRepository` gauna `PDO`.

Taip lengviau keisti implementacijas (pvz., kitą DB) ir testuoti.

Kiti principai (OCP, LSP, ISP) – išplaukia iš šio atsakomybės atskyrimo: sluoksniai nėra per daug „protingi“ ir nedubliuoja funkcionalumo.

---

## 🧩 4. Naudoti design pattern'ai

### Repository Pattern

**Kur:** `src/ApplicationRepository.php`  
**Kodėl:**

- aiškus duomenų prieigos sluoksnis,
- galima pakeisti SQLite į kitą DB be pokyčių Controller/Service sluoksniuose,
- palengvina unit testų rašymą.

### Service Layer Pattern

**Kur:** `src/ApplicationService.php`  
**Kodėl:**

- visos taisyklės (max 3 paraiškos per tipą, statusų keitimas, komentaro validacija) vienoje vietoje;
- Controller neturi verslo logikos – jis tik perduoda duomenis į Service.

### Paprastas Routing Pattern

**Kur:** `public/index.php` + `src/Router.php`

- `/login` ir `/register` nukreipiami per vieną router’į,
- paraiškų dalis (`/applications`) kol kas naudoja klasikinius entrypoint’us (`public/applications/*.php`).

---

## 🔐 5. Saugumo sprendimai

### 5.1. SQL Injection apsauga

- Visi užklausų parametrai paduodami per `PDO::prepare()` / `execute()`:
  - nenaudojama stringų konkatenacija `"... WHERE id=$id"`,
  - naudojami placeholder’iai `:id`, `:student_id`, `:email` ir t. t.
- `PDO::ATTR_EMULATE_PREPARES = false` – naudojami tik tikri prepared statements.

### 5.2. XSS apsauga

Visose view:

```php
htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
```

Taip vartotojo įvestas tekstas nėra vykdomas kaip HTML/JS naršyklėje.

### 5.3. CSRF apsauga

Failas: `src/csrf.php`

Kiekviena POST forma:

```html
<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>" />
```

Serverio pusėje tikrinama:

```php
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    // klaida
}
```

### 5.4. Login rate limiting

- Po kelių nesėkmingų bandymų (pvz. 5) – login blokavimas tam tikram laikui,
- sumažina bruteforce atakų riziką.

### 5.5. Sesijų apsauga

- `session_regenerate_id(true)` po sėkmingo prisijungimo,
- sumažina session fixation riziką.

### 5.6. Spam apsauga

- `ApplicationRepository::countRecentDraftsForStudent(...)` + `ApplicationService::createDraftForStudent(...)`:
  - neleidžia sukurti per daug ruošinių per trumpą laiką (pvz. >5 per 60 sekundžių).

---

## 🧪 6. Unit testai (PHPUnit)

Testai yra suskirstyti:

### 6.1. Verslo logikos testai

Failas: `tests/ApplicationServiceTest.php`

Tikrina:

- ruošinio kūrimą (`createDraftForStudent`) + validaciją,
- max 3 `submitted` paraiškas vieno tipo studentui,
- pateikimą `draft → submitted`,
- draudimą pateikti kito studento paraišką,
- patvirtinimą `submitted → approved` (tik `submitted` keičiasi),
- atmetimą `submitted → rejected` su komentaru,
- klaidą, kai atmetimo komentaras tuščias,
- ruošinių rate limit (per daug bandymų per minutę).

Naudojama in-memory SQLite (`sqlite::memory:`), kad testai neapkrautų realios DB.

### 6.2. REST API testai

Papildomi unit/integraciniai testai API logikai (pvz. `tests/ApiLoginTest.php`, `tests/ApiApplicationsApiTest.php`) tikrina:

- `/api/login` – sėkminga ir nesėkminga autentifikacija,
- `/api/applications` – sąrašo gavimą prisijungus,
- `/api/applications` – ruošinio sukūrimą per JSON,
- `/api/applications` – klaidą, jei neautentifikuota.

(API sluoksnis testuojamas per helper funkcijas, kurios grąžina [status, body] be HTTP header’io priklausomybės.)

### Testų paleidimas

```bash
composer install
vendor/bin/phpunit
```

Tikėtinas rezultatas:

```text
OK (10+ tests, 30+ assertions)
```

---

## 🌐 7. Pilnas REST API (`/api/...`)

REST API sluoksnis leidžia dirbti su sistema be HTML – per JSON.

### 7.1. `/api/login` – prisijungimas (POST)

Failas: `public/api/login.php`  
Logika: `src/ApiLogin.php` (`api_login_handle()` funkcija).

**Request:**

```http
POST /api/login HTTP/1.1
Host: localhost:8000
Content-Type: application/json

{
  "email": "student@example.com",
  "password": "student123"
}
```

**Atsakymas (200 OK):**

```json
{
  "success": true,
  "user": {
    "id": 2,
    "name": "Student User",
    "role": "student"
  }
}
```

Nesėkmės atveju – `401` ir:

```json
{
  "success": false,
  "error": "Neteisingas el. paštas arba slaptažodis."
}
```

Sesija (`PHPSESSID`) nustatoma taip pat, kaip ir HTML login.

---

### 7.2. `/api/applications` – sąrašas ir kūrimas

Failas: `public/api/applications.php`  
Logika: `src/ApiApplications.php` (`api_applications_handle()`).

**GET /api/applications**

- jei prisijungęs studentas – grąžina JO paraiškas,
- jei prisijungęs adminas – grąžina VISAS paraiškas.

```http
GET /api/applications HTTP/1.1
Cookie: PHPSESSID=...
```

**Atsakymas (200 OK):**

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

Jeigu neautentifikuota – `401` su JSON klaida.

---

**GET /api/applications?id={id}**

Gauti vieną paraišką.

- studentas gali matyti tik savo paraiškas,
- adminas gali matyti bet kurią.

```http
GET /api/applications?id=1
Cookie: PHPSESSID=...
```

**Atsakymas (200 OK):**

```json
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
```

Jei nerandama – `404`.

---

**POST /api/applications** – sukurti ruošinį (tik studentui)

```http
POST /api/applications HTTP/1.1
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

Jei verslo taisyklė grąžina klaidą (pvz. per daug ruošinių) – `400`:

```json
{
  "success": false,
  "error": "Per daug bandymų sukurti paraiškas. Palaukite minutę ir bandykite vėl."
}
```

---

### 7.3. Statuso keitimas per API (submit/approve/reject)

Tam naudojamas **PATCH** metodas su `action` lauku.

**Submit (studentas)**

```http
PATCH /api/applications?id=1 HTTP/1.1
Content-Type: application/json
Cookie: PHPSESSID=... (studento sesija)

{
  "action": "submit"
}
```

**Atsakymas (200 OK):**

```json
{
  "success": true,
  "message": "Paraiška sėkmingai pateikta."
}
```

---

**Approve (adminas)**

```http
PATCH /api/applications?id=1 HTTP/1.1
Content-Type: application/json
Cookie: PHPSESSID=... (admino sesija)

{
  "action": "approve"
}
```

---

**Reject (adminas)**

```http
PATCH /api/applications?id=1 HTTP/1.1
Content-Type: application/json
Cookie: PHPSESSID=... (admino sesija)

{
  "action": "reject",
  "comment": "Netinkami duomenys"
}
```

**Atsakymas (200 OK):**

```json
{
  "success": true,
  "message": "Paraiška atmesta.",
  "comment": "Netinkami duomenys"
}
```

Jei komentaras tuščias – `400`, su klaidos žinute iš Service.

---

## 🧪 8. Kaip pačiam patestuoti REST API

### 8.1. Paleisk serverį

```bash
php -S localhost:8000 -t public
```

### 8.2. Testavimas su `curl`

1. **Login studentu (gausi cookie)**

```bash
curl -i -c cookies.txt   -H "Content-Type: application/json"   -d '{"email":"student@example.com","password":"student123"}'   http://localhost:8000/api/login
```

2. **Sukurti paraišką per API**

```bash
curl -i -b cookies.txt   -H "Content-Type: application/json"   -d '{"title":"API Paraiška","description":"Aprašymas","type":"Stipendija"}'   http://localhost:8000/api/applications
```

3. **Gauti sąrašą**

```bash
curl -i -b cookies.txt http://localhost:8000/api/applications
```

4. **Pateikti paraišką (submit)**

```bash
curl -i -b cookies.txt   -X PATCH   -H "Content-Type: application/json"   -d '{"action":"submit"}'   "http://localhost:8000/api/applications?id=1"
```

5. **Login adminu ir patvirtinti**

```bash
curl -i -c admin_cookies.txt   -H "Content-Type: application/json"   -d '{"email":"admin@example.com","password":"admin123"}'   http://localhost:8000/api/login

curl -i -b admin_cookies.txt   -X PATCH   -H "Content-Type: application/json"   -d '{"action":"approve"}'   "http://localhost:8000/api/applications?id=1"
```

### 8.3. Testavimas su Postman / Thunder Client

1. Sukurk **POST** request į `/api/login` su JSON body (email/password).
2. Išsaugok cookie (Postman tai daro automatiškai).
3. Sukurk naujus request’us:
   - GET `/api/applications`
   - POST `/api/applications`
   - PATCH `/api/applications?id=...` su atitinkamu body.

---

## 📁 9. Projekto struktūra (su API)

```text
students-app/
│
├── public/
│   ├── index.php
│   ├── login.php          # legacy
│   ├── register.php       # legacy
│   ├── logout.php
│   ├── api/
│   │   ├── login.php
│   │   └── applications.php
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
│   ├── Router.php
│   ├── ApplicationRepository.php
│   ├── ApplicationService.php
│   ├── ApplicationController.php
│   ├── ApiLogin.php            # API login logika
│   └── ApiApplications.php     # API applications logika
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
│   ├── ApiLoginTest.php
│   ├── ApiApplicationsApiTest.php
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

## 👤 10. Autorius

Įrašykite savo duomenis:

- **Povilas Urbonas**
- **El. paštas**
- **GitHub profilis https://github.com/PovilasU**
